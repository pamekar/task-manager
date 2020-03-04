###### Basic Task Manager

This is a REST application grants a user the ability to see a list of tasks for my day, so that they can be done one by one. 

Symfony/Skeleton framework was used in developing this. It is currently hosted on Heroku @ https://basic-taskmanager.herokuapp.com/.

The API endpoints can be imported into postman for testing using this Postman collection url: https://www.getpostman.com/collections/991721ed8d19cbf39c18.

Tests were made for this application, and can be found in the **tests** folder. Below are the list of endpoints accessible on this app.

|  Name                        | Method   | Scheme  | Host  | Path                     |
| ---------------------------- | -------- | ------- | ----- | ------------------------ | 
|  _preview_error              | ANY      | ANY     | ANY   | /_error/{code}.{_format} | 
|  app_security_createclient   | POST     | ANY     | ANY   | /auth/createClient       | 
|  app_security_createuser     | POST     | ANY     | ANY   | /auth/register           | 
|  app_security_getloggeduser  | GET      | ANY     | ANY   | /api/user                | 
|  api_app_task_indextasks     | GET      | ANY     | ANY   | /api/tasks/              | 
|  api_app_task_showtasks      | GET      | ANY     | ANY   | /api/tasks/{id}          | 
|  api_app_task_storetasks     | POST     | ANY     | ANY   | /api/tasks/              | 
|  api_app_task_updatetasks    | PUT      | ANY     | ANY   | /api/tasks/{id}          | 
|  api_app_task_deletetasks    | DELETE   | ANY     | ANY   | /api/tasks/{id}          | 
|  fos_oauth_server_token      | GET|POST | ANY     | ANY   | /oauth/v2/token          | 
|  fos_oauth_server_authorize  | GET|POST | ANY     | ANY   | /oauth/v2/auth           | 



