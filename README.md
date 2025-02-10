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

doctor/schedule_work/index

<!--
		
			<table class="table">
				<thead>
					<tr>
						<th>Id</th>
						<th>Date</th>
						<th>MaxPatient</th>
						{# <th>Status</th> #}
						<th>TimeSlots</th>
						<th>actions</th>
					</tr>
				</thead>
				<tbody>
					{% for schedule_work in schedule_works %}
						<tr>
							<td>{{ schedule_work.id }}</td>
							<td>{{ schedule_work.date ? schedule_work.date|date('Y-m-d') : '' }}</td>
							<td>{{ schedule_work.maxPatient }}</td>
							{# <td>{{ schedule_work.status }}</td> #}
							{# <td>{{ schedule_work.timeSlots ? schedule_work.timeSlots|json_encode : '' }}</td> #}
							<td>
								{% if schedule_work.timeSlots is iterable %}
									{% for slot in schedule_work.timeSlots %}
		
										<div id="slot_monday" class="tab-pane fade show active">
											<h4 class="card-title d-flex justify-content-between">
												<span>Time Slots</span>
												<a class="edit-link" data-toggle="modal" href="#edit_time_slot">
													<i class="fa fa-edit mr-1"></i>Edit</a>
											</h4>
		
											<div class="doc-times">
												<div class="doc-slot-list">
													{{ slot ? slot|json_encode : '' }}
													<a href="javascript:void(0)" class="delete_schedule">
														<i class="fa fa-times"></i>
													</a>
												</div>
											</div>
										</div>
									{% endfor %}
								{% endif %}
							</td>
							<td>
								<a href="{{ path('app_doctor_schedule_show', {'id': schedule_work.id}) }}">show</a>
								<a href="{{ path('app_doctor_schedule_edit', {'id': schedule_work.id}) }}">edit</a>
							</td>
						</tr>
					{% else %}
						<tr>
							<td colspan="6">no records found</td>
						</tr>
					{% endfor %}
				</tbody>
			</table>
		
		-->