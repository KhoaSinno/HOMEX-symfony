# HOMEX-Symfony

## 🚀 Symfony Command Usage Guide

### 📌 Overview

Chào mừng bạn đến với tài liệu hướng dẫn sử dụng các lệnh Symfony! Tài liệu này cung cấp danh sách các lệnh quan trọng giúp bạn phát triển và thiết lập dự án một cách dễ dàng.

---

## 🔍 General Commands

### 🛠 Debugging Data

```bash
dump(your data)
die()
```

> Sử dụng `dump()` để hiển thị dữ liệu phục vụ mục đích debug.

---

## ⚙️ Setting Up Symfony CLI & Tools

### ✅ PowerShell Command to Set Execution Policy

```bash
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

> Cho phép chạy các script từ xa trên hệ thống của bạn.

### 📥 Install Symfony CLI via Scoop

```bash
Invoke-RestMethod -Uri https://get.scoop.sh | Invoke-Expression
scoop install symfony-cli
```

### 🎼 Install Composer

[Download Composer](https://getcomposer.org/download/)

### 🔧 Install Git

[Download Git](https://git-scm.com/)

---

## 🏗️ Creating a Symfony Project

### 🌐 Create New Symfony Web App

```bash
symfony new --webapp my_project
```

### 🏆 Create Full Symfony Project

```bash
symfony new --full my_project
```

---

## 🛢 Database Configuration

### 🔧 Configure Environment Variables

```bash
config .env DBMS MYSQL
```

> Cấu hình DBMS sử dụng MySQL trong tệp `.env`.

---

## 📜 Doctrine Commands

### 🏗 Create Database

```bash
php bin/console doctrine:database:create
```

### 🔹 Generate Entity

```bash
php bin/console make:entity Category
```

### 🛠 Generate & Run Migrations

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 🔄 Generate CRUD for Category

```bash
php bin/console make:crud Category
```

---

## 🏗 Fixtures (Seeding Data)

### 🔍 What are Fixtures?

Fixtures trong Symfony tương tự như seeders. Chúng giúp tải dữ liệu mẫu vào cơ sở dữ liệu, hữu ích cho việc thử nghiệm và thiết lập dữ liệu ban đầu.

### 🏗 Create a New Fixtures

```bash
php symfony console make:fixture AppointmentFixtures
```

### 📥 Load Dummy Data from Fixture

```bash
symfony console doctrine:fixtures:load
```

---

## 🌍 Multi-Language Translation

Cấu hình dịch thuật trong `config/packages/translation.yaml`

### 📝 Check Messages (English → Vietnamese)

```bash
php bin/console debug:translation vi messages
```

---

## 🤖 AI-Powered Features for Scheduling System

- **📅 Gợi ý lịch hẹn thông minh**: AI có thể phân tích lịch trống của bác sĩ và thói quen đặt lịch của bệnh nhân để gợi ý khung giờ phù hợp nhất.
- **💬 Chatbot hỗ trợ đặt lịch**: Tích hợp chatbot AI để hỗ trợ bệnh nhân đặt lịch, hủy lịch hoặc thay đổi thời gian một cách nhanh chóng.
- **📊 Phân tích xu hướng & dự báo**: AI có thể phân tích dữ liệu lịch sử để dự đoán các khung giờ cao điểm hoặc thời gian có khả năng bị hủy cao.
- **🔔 Nhắc nhở tự động**: AI có thể cá nhân hóa nhắc nhở lịch hẹn dựa trên thói quen của bệnh nhân để giảm tỷ lệ vắng mặt.

---

# 📖 Hướng dẫn Kỹ thuật - Hệ thống Đặt lịch Khám bệnh Online  

## **🔍 Cách Khách hàng Tìm kiếm Thông tin trên Trang chủ**  

Khách hàng có thể tiếp cận thông tin qua **4 cách**:  

1️⃣ **Tìm kiếm (Search)**: Nhập từ khóa vào thanh tìm kiếm để tìm nhanh thông tin mong muốn.  

2️⃣ **Bấm vào Danh mục chuyên khoa**: Lọc thông tin theo từng chuyên khoa cụ thể.  

3️⃣ **Bấm vào bác sĩ**: Xem danh sách bác sĩ và lựa chọn bác sĩ phù hợp.  

4️⃣ **Bấm vào "Xem thêm"**: Xem thêm thông tin chi tiết về chuyên khoa, bác sĩ, hoặc các dịch vụ liên quan.  

# Google Login Integration in Symfony

## 🛠 Prerequisites

- Symfony 6+
- Composer
- Google Cloud Console account

## 📌 Step 1: Install Required Packages

Run the following command to install 2 package:

```bash
composer require symfony/ux-oauth2 symfony/security-bundle knpuniversity/oauth2-client-bundle league/oauth2-google
```

## 📌 Step 2: Create Google OAuth App

1. Go to [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project or select an existing one.
3. Navigate to **APIs & Services > Credentials**.
4. Click **Create Credentials > OAuth 2.0 Client ID**.
5. Set **Authorized Redirect URI** to:

   ``` bash
   https://127.0.0.1:8000/connect/google/check
   ```

6. Copy the **Client ID** and **Client Secret**.

## 📌 Step 3: Configure Environment Variables

Add the credentials to your `.env` file:

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://127.0.0.1:8000/connect/google/check
```

## 📌 Step 4: Configure Security Bundle

Edit `config/packages/security.yaml`:

```yaml
security:
    ...
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            entry_point: form_login
            custom_authenticators: // 📌 Important to config
                - App\Security\GoogleAuthenticator

    access_control:
       ...
```

## 📌 Step 5: Configure OAuth2 Client

We can Create new file or Edit  existed file `config/packages/knpu_oauth2_client.yaml`:

```yaml
knpu_oauth2_client:
    clients:
        google:
            type: google
            client_id: '%env(GOOGLE_CLIENT_ID)%'
            client_secret: '%env(GOOGLE_CLIENT_SECRET)%'
            redirect_route: connect_google_check
            user_fields: [email, name, picture]
```

## 📌 Step 6: Create GoogleAuthenticator

Create `src/Security/GoogleAuthenticator.php`:

```php
<?php

namespace App\Security;

use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\{UserBadge, CsrfTokenBadge};
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\OAuth2Credentials;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): bool
    {
        return 'connect_google_check' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $client = new GoogleClient();
        $accessToken = $client->getAccessToken();
        $googleUser = $client->fetchUserFromToken($accessToken);

        return new Passport(
            new UserBadge($googleUser->getEmail()),
            new OAuth2Credentials($accessToken),
            [new CsrfTokenBadge('authenticate', $request->get('_csrf_token'))]
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    public function onAuthenticationFailure(Request $request, $exception): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
```

## 📌 Step 7: Create Login Routes

Edit `src/Controller/SecurityController.php`:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(GoogleClient $client)
    {
        return $client->redirect(['email', 'profile']);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck()
    {
        // Symfony handles authentication automatically
    }
}
```

## 📌 Step 8: Useful Login Template

Edit `templates/security/login.html.twig`:

```twig
    <a href="{{ path('connect_google_start') }}">
        Login with Google
    </a>
```

## 📌 Step 9: Add new properties

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
     #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }
}
```

## 📌 Step 10: add into bundles.php

 Open `config\bundles.php`:

 ```php
 return[
    ...
     KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle::class => ['all' => true],
 ]

 ```

## 📌 Step 9: Update Database

Run the following commands:

```bash
php bin/console make:user
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## ✅ Done

Test it at: `http://127.0.0.1:8000/login`. 🎉

# Iteractions Momo

## Data: Response from Momo

```bash

MomoController.php on line 56:
array:13 [▼
  "partnerCode" => "MOMOBKUN20180529"
  "orderId" => "1740566073"
  "requestId" => "1740566073"
  "amount" => 100000
  "responseTime" => 1740566074530
  "message" => "Thành công."
  "resultCode" => 0
  "payUrl" => "https://test-payment.momo.vn/v2/gateway/pay?t=TU9NT0JLVU4yMDE4MDUyOXwxNzQwNTY2MDcz&s=04a8b962e67ab6d3ed63ba0e05f0489d653ec9e13bf7aa733404be1ea30ea36b"
  "deeplink" => "momo://app?action=payWithApp&isScanQR=false&revampAutoPick=false&serviceType=app&sid=TU9NT0JLVU4yMDE4MDUyOXwxNzQwNTY2MDcz&v=3.0"
  "qrCodeUrl" => "
00020101021226110007vn.momo38620010A00000072701320006970454011899MM25057O000003300208QRIBFTTA530370454061000005802VN62480515MMTL289KclD6YQR0825Thanh toan qua ma
 ▶
"
  "applink" => "
https://test-applinks.momo.vn/payment/v2?action=payWithApp&isScanQR=false&revampAutoPick=false&serviceType=app&sid=TU9NT0JLVU4yMDE4MDUyOXwxNzQwNTY2MDcz&v=3.0&de
 ▶
"
  "deeplinkMiniApp" => "momo://app?action=payWithApp&isScanQR=false&revampAutoPick=false&serviceType=miniapp&sid=TU9NT0JLVU4yMDE4MDUyOXwxNzQwNTY2MDcz&v=3.0"
  "signature" => "7443196e864f75a2e726d52e332607ca322ee1bbf8bb5722d4c5d4c38357e58f"
]
```

## How to show notice

``` php

{% if app.request.get('_route') == 'app_doctor_appointment' %}
       {% for label, messages in app.flashes %}
        {% for message in messages %}
         <div class="alert alert-{{ label }}">
          {{ message }}
         </div>
        {% endfor %}
       {% endfor %}
      {% endif %}
```

``` php
            $this->addFlash('danger', 'Bạn chỉ có thể hủy lịch hẹn trước 2 giờ.');

```
