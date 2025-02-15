# HOMEX-symfony

## Symfony Command Usage

## Overview

This document provides a comprehensive guide to commonly used Symfony commands for development and project setup.

---

## General Commands

### Display Data (Debugging Purpose)

```bash
dump()
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
