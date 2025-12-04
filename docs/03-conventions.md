# 开发规范

## API 响应格式

所有 API 响应使用统一格式：

```json
{
    "code": 200,
    "message": "Success",
    "data": {}
}
```

### 响应码规则

| 范围 | 含义                           |
| ---- | ------------------------------ |
| 200  | 成功                           |
| 4xx  | 客户端错误（对应 HTTP 状态码） |
| 5xx  | 服务端错误                     |
| 1xxx | 用户相关业务错误               |
| 2xxx | 资源相关业务错误               |
| 3xxx | 权限相关业务错误               |
| 9xxx | 第三方服务错误                 |

### 使用方法

Controller 继承自 `App\Http\Controllers\Controller`，自动获得响应方法：

```php
use App\Enums\ResponseCode;

class UserController extends Controller
{
    // 成功响应
    public function show(User $user)
    {
        return $this->success($user);
        // {"code": 200, "message": "Success", "data": {...}}
    }

    // 成功响应 + 自定义消息
    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());
        return $this->success($user, 'User created successfully');
    }

    // 失败响应
    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->validated())) {
            return $this->fail(ResponseCode::INVALID_CREDENTIALS);
            // {"code": 1004, "message": "Invalid Username or Password"}
        }
        // ...
    }

    // 未授权响应（快捷方法）
    public function profile()
    {
        if (!auth()->check()) {
            return $this->unauthorized();
            // {"code": 401, "message": "Unauthorized, please login first"}
        }
        // ...
    }
}
```

### 多语言响应

响应消息自动根据当前语言环境返回对应语言：

```php
// 当 locale = 'en'
return $this->fail(ResponseCode::USER_NOT_FOUND);
// {"code": 1001, "message": "User Not Found"}

// 当 locale = 'zh_CN'
return $this->fail(ResponseCode::USER_NOT_FOUND);
// {"code": 1001, "message": "用户不存在"}
```

### 添加新的响应码

1. 在 `app/Enums/ResponseCode.php` 添加枚举值：

```php
// 订单相关 4xxx
case ORDER_NOT_FOUND = 4001;
case ORDER_ALREADY_PAID = 4002;
```

2. 在 `key()` 方法添加映射：

```php
self::ORDER_NOT_FOUND => 'order_not_found',
self::ORDER_ALREADY_PAID => 'order_already_paid',
```

3. 在 `httpStatus()` 方法添加 HTTP 状态码映射：

```php
self::ORDER_NOT_FOUND => 404,
self::ORDER_ALREADY_PAID => 400,
```

4. 在语言文件中添加翻译：

```php
// lang/en/response.php
'order_not_found' => 'Order Not Found',
'order_already_paid' => 'Order Already Paid',

// lang/zh_CN/response.php
'order_not_found' => '订单不存在',
'order_already_paid' => '订单已支付',
```

## Controller 规范

### 保持精简

Controller 只负责：

1. 接收请求
2. 调用 Action
3. 返回响应

```php
// ✅ 推荐
public function store(StoreUserRequest $request, CreateUserAction $action)
{
    $data = UserData::from($request);
    $user = $action->execute($data);
    return $this->success(new UserResource($user));
}

// ❌ 避免：在 Controller 中写业务逻辑
public function store(StoreUserRequest $request)
{
    $user = User::create($request->validated());
    $user->assignRole('user');
    Mail::to($user)->send(new WelcomeMail());
    event(new UserRegistered($user));
    return $this->success($user);
}
```

## Action 规范

Action 是单一职责的业务逻辑类。

### 命名规范

-   动词 + 名词 + Action
-   例如：`CreateUserAction`、`UpdateOrderAction`、`SendNotificationAction`

### 结构

```php
namespace Modules\UserManagement\Actions;

use Modules\UserManagement\Data\UserData;
use Modules\UserManagement\Models\User;

class CreateUserAction
{
    public function execute(UserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data->toArray());
            $user->assignRole('user');

            // 异步任务放入队列
            SendWelcomeEmailJob::dispatch($user);

            return $user;
        });
    }
}
```

## Data (DTO) 规范

使用 `spatie/laravel-data` 创建类型安全的数据对象。

### 命名规范

-   名词 + Data
-   例如：`UserData`、`OrderData`、`CreateUserData`

### 结构

```php
namespace Modules\UserManagement\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Max;

class UserData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Required, Email]
        public string $email,

        #[StringType, Max(20)]
        public ?string $phone = null,
    ) {}
}
```

### 验证

Data 类自带验证，可以替代 FormRequest：

```php
// 自动验证并创建
$data = UserData::from($request);

// 验证失败会抛出 ValidationException
```

## 测试规范

使用 Pest 编写测试。

```php
use Modules\UserManagement\Models\User;

it('can create a user', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'code' => 200,
            'message' => 'Success',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

it('returns validation error for invalid email', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'code' => 422,
        ]);
});
```

### 运行测试

```bash
# 运行所有测试
php artisan test

# 运行指定文件
php artisan test tests/Feature/UserTest.php

# 运行指定测试
php artisan test --filter="can create a user"
```
