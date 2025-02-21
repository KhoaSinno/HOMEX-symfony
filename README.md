# HOMEX-symfony

## Symfony Command Usage

## Overview

This document provides a comprehensive guide to commonly used Symfony commands for development and project setup.

---

## General Commands

### Display Data (Debugging Purpose)

```bash
dump(your data)
die()
```

> Use `dump()` to print data for debugging.

---

## Setting Up Symfony CLI and Tools

### PowerShell Command to Set Execution Policy

```bash
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

> Allows the execution of remote scripts on your system.

### Install Symfony CLI via Scoop

```bash
Invoke-RestMethod -Uri https://get.scoop.sh | Invoke-Expression
scoop install symfony-cli
```

### Install Composer

[Download Composer](https://getcomposer.org/download/)

### Install Git

[Download Git](https://git-scm.com/)

---

## Creating a Symfony Project

### Create New Symfony Web App

```bash
symfony new --webapp my_project
```

### Create Full Symfony Project

```bash
symfony new --full my_project
```

---

## Database Configuration

### Configure Environment Variables

```bash
config .env DBMS MYSQL
```

> Set the DBMS to MySQL in the `.env` configuration file.

---

## Doctrine Commands

### Create Database

```bash
php bin/console doctrine:database:create
```

### Generate Entity

```bash
php bin/console make:entity category
```

### Generate and Run Migrations

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Generate CRUD for Category

```bash
php bin/console make:crud Category
```

---

## Fixtures

### What are Fixtures?

Fixtures in Symfony are similar to seeders. They allow you to load initial data into the database, which is useful for testing and setting up demo data.

### Create a new Fixtures

```bash
php symfony console make:fixture AppointmentFixtures
```

After import dummy data in this fixture `run`:

```bash
symfony console doctrine:fixtures:load
```

## How to translation in system

`config/packages/translation.yaml`

en -> vi

- Check messages

```bash
php bin/console debug:translation vi messages
```

- Gợi ý lịch hẹn thông minh: AI có thể phân tích lịch trống của bác sĩ và thói quen đặt lịch của bệnh nhân để gợi ý khung giờ phù hợp nhất.

- Chatbot hỗ trợ đặt lịch: Tích hợp chatbot AI để hỗ trợ bệnh nhân đặt lịch, hủy lịch hoặc thay đổi thời gian một cách nhanh chóng.

- Phân tích xu hướng & dự báo: AI có thể phân tích dữ liệu lịch sử để dự đoán các khung giờ cao điểm hoặc thời gian có khả năng bị hủy cao.

- Nhắc nhở tự động: AI có thể cá nhân hóa nhắc nhở lịch hẹn dựa trên thói quen của bệnh nhân để giảm tỷ lệ vắng mặt.

# 📖 Hướng dẫn Kỹ thuật - Hệ thống Đặt lịch Khám bệnh Online  

## **🔍 Cách Khách hàng Tìm kiếm Thông tin trên Trang chủ**  

Khách hàng có thể tiếp cận thông tin qua **4 cách**:  

1️⃣ **Tìm kiếm (Search)**: Nhập từ khóa vào thanh tìm kiếm để tìm nhanh thông tin mong muốn.  

2️⃣ **Bấm vào Danh mục chuyên khoa**: Lọc thông tin theo từng chuyên khoa cụ thể.  

3️⃣ **Bấm vào bác sĩ**: Xem danh sách bác sĩ và lựa chọn bác sĩ phù hợp.  

4️⃣ **Bấm vào "Xem thêm"**: Xem thêm thông tin chi tiết về chuyên khoa, bác sĩ, hoặc các dịch vụ liên quan.  
