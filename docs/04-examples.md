# 完整示例：用户管理模块

本示例展示如何创建一个完整的用户管理模块，包含：

-   CRUD 操作
-   认证（登录/登出）
-   权限控制
-   文件上传（头像）
-   队列任务（发送邮件）
-   列表查询（过滤/排序）

## 1. 创建模块

```bash
php artisan make:module UserManagement
```

## 2. 目录结构

```
app-modules/user-management/src/
├── Actions/
│   ├── CreateUserAction.php
│   ├── UpdateUserAction.php
│   └── DeleteUserAction.php
├── Data/
│   ├── UserData.php
│   └── LoginData.php
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   └── UserController.php
│   └── Resources/
│       └── UserResource.php
├── Jobs/
│   └── SendWelcomeEmailJob.php
└── Models/
    └── User.php
```

## 3. Data (DTO)

### UserData.php

```php
<?php

namespace Modules\UserManagement\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Illuminate\Http\UploadedFile;

class UserData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Required, Email]
        public string $email,

        #[Required, StringType, Min(8), Confirmed]
        public ?string $password = null,

        #[StringType, Max(20)]
        public ?string $phone = null,

        public ?UploadedFile $avatar = null,
    ) {}
}
```

### LoginData.php

```php
<?php

namespace Modules\UserManagement\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;

class LoginData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,

        #[Required]
        public string $password,
    ) {}
}
```

## 4. Actions

### CreateUserAction.php

```php
<?php

namespace Modules\UserManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\UserManagement\Data\UserData;
use Modules\UserManagement\Jobs\SendWelcomeEmailJob;
use Modules\UserManagement\Models\User;

class CreateUserAction
{
    public function execute(UserData $data): User
    {
        return DB::transaction(function () use ($data) {
            // 创建用户
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'phone' => $data->phone,
            ]);

            // 分配默认角色
            $user->assignRole('user');

            // 上传头像
            if ($data->avatar) {
                $user->addMedia($data->avatar)->toMediaCollection('avatar');
            }

            // 异步发送欢迎邮件
            SendWelcomeEmailJob::dispatch($user);

            return $user;
        });
    }
}
```

### UpdateUserAction.php

```php
<?php

namespace Modules\UserManagement\Actions;

use Illuminate\Support\Facades\Hash;
use Modules\UserManagement\Data\UserData;
use Modules\UserManagement\Models\User;

class UpdateUserAction
{
    public function execute(User $user, UserData $data): User
    {
        $updateData = [
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
        ];

        if ($data->password) {
            $updateData['password'] = Hash::make($data->password);
        }

        $user->update($updateData);

        if ($data->avatar) {
            $user->addMedia($data->avatar)->toMediaCollection('avatar');
        }

        return $user->fresh();
    }
}
```

## 5. Controllers

### AuthController.php

```php
<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Enums\ResponseCode;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\UserManagement\Actions\CreateUserAction;
use Modules\UserManagement\Data\LoginData;
use Modules\UserManagement\Data\UserData;
use Modules\UserManagement\Http\Resources\UserResource;

class AuthController extends Controller
{
    /**
     * 用户注册
     */
    public function register(CreateUserAction $action)
    {
        $data = UserData::from(request());
        $user = $action->execute($data);
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], __('response.success'));
    }

    /**
     * 用户登录
     */
    public function login()
    {
        $data = LoginData::from(request());

        if (!Auth::attempt(['email' => $data->email, 'password' => $data->password])) {
            return $this->fail(ResponseCode::INVALID_CREDENTIALS);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * 用户登出
     */
    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        return $this->success(null, __('response.success'));
    }

    /**
     * 获取当前用户信息
     */
    public function me()
    {
        return $this->success(new UserResource(auth()->user()));
    }
}
```

### UserController.php

```php
<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Enums\ResponseCode;
use App\Http\Controllers\Controller;
use Modules\UserManagement\Actions\CreateUserAction;
use Modules\UserManagement\Actions\UpdateUserAction;
use Modules\UserManagement\Data\UserData;
use Modules\UserManagement\Http\Resources\UserResource;
use Modules\UserManagement\Models\User;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class UserController extends Controller
{
    /**
     * 用户列表（支持过滤、排序）
     *
     * GET /api/users?filter[status]=active&filter[role]=admin&sort=-created_at
     */
    public function index()
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                'name',
                'email',
                AllowedFilter::exact('status'),
                AllowedFilter::exact('role'),
            ])
            ->allowedSorts(['created_at', 'name', 'email'])
            ->allowedIncludes(['roles', 'permissions'])
            ->paginate(request('per_page', 15));

        return $this->success([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * 用户详情
     */
    public function show(User $user)
    {
        return $this->success(new UserResource($user->load('roles', 'permissions')));
    }

    /**
     * 创建用户
     */
    public function store(CreateUserAction $action)
    {
        $data = UserData::from(request());
        $user = $action->execute($data);

        return $this->success(new UserResource($user), __('response.success'));
    }

    /**
     * 更新用户
     */
    public function update(User $user, UpdateUserAction $action)
    {
        $data = UserData::from(request());
        $user = $action->execute($user, $data);

        return $this->success(new UserResource($user));
    }

    /**
     * 删除用户
     */
    public function destroy(User $user)
    {
        $user->delete();

        return $this->success(null, __('response.success'));
    }
}
```

## 6. Resource

### UserResource.php

```php
<?php

namespace Modules\UserManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->getFirstMediaUrl('avatar'),
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            'permissions' => $this->whenLoaded('permissions', fn() => $this->permissions->pluck('name')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

## 7. Job

### SendWelcomeEmailJob.php

```php
<?php

namespace Modules\UserManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\UserManagement\Models\User;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function handle(): void
    {
        // Mail::to($this->user)->send(new WelcomeMail($this->user));
        logger()->info("Welcome email sent to {$this->user->email}");
    }
}
```

## 8. 路由

### routes/api.php

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\AuthController;
use Modules\UserManagement\Http\Controllers\UserController;

// 公开路由
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 需要认证的路由
Route::middleware('auth:sanctum')->group(function () {
    // 认证相关
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // 用户管理（需要权限）
    Route::middleware('permission:manage users')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
```

## 9. 测试

### tests/Feature/UserManagement/AuthTest.php

```php
<?php

use Modules\UserManagement\Models\User;

it('can register a new user', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'code',
            'message',
            'data' => ['user', 'token'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

it('can login with valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertSuccessful()
        ->assertJson(['code' => 200]);
});

it('returns error for invalid credentials', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson(['code' => 1004]);
});

it('can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/logout');

    $response->assertSuccessful();
});
```

### tests/Feature/UserManagement/UserTest.php

```php
<?php

use Modules\UserManagement\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // 创建权限
    Permission::create(['name' => 'manage users']);
    $adminRole = Role::create(['name' => 'admin']);
    $adminRole->givePermissionTo('manage users');
});

it('can list users with filters', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    User::factory()->count(5)->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/users?sort=-created_at');

    $response->assertSuccessful()
        ->assertJson(['code' => 200]);
});

it('can create a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)
        ->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
    ]);
});

it('denies access without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/users');

    $response->assertStatus(403);
});
```

## 10. 多语言响应示例

### 设置语言中间件

```php
// app/Http/Middleware/SetLocale.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language', 'en');

        if (in_array($locale, ['en', 'zh_CN'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
```

### 注册中间件

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->api(prepend: [
        \App\Http\Middleware\SetLocale::class,
        EnsureFrontendRequestsAreStateful::class,
    ]);
})
```

### 请求示例

```bash
# 英文响应
curl -H "Accept-Language: en" http://localhost:8000/api/users/999
# {"code": 1001, "message": "User Not Found"}

# 中文响应
curl -H "Accept-Language: zh_CN" http://localhost:8000/api/users/999
# {"code": 1001, "message": "用户不存在"}
```
