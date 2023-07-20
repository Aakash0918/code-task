# code-task
# MVC Demostration
Whenever you create an application, you have to find a way to organize the code to make it simple to locate the proper files and make it simple to maintain. Like most of the web frameworks, CodeIgniter uses the Model, View, Controller (MVC) pattern to organize the files. This keeps the data, the presentation, and flow through the application as separate parts.

It should be noted that there are many views on the exact roles of each element, but this document describes our take on it. If you think of it differently, you’re free to modify how you use each piece as you need.

Models manage the data of the application and help to enforce any special business rules the application might need.

Views are simple files, with little to no logic, that display the information to the user.

Controllers act as glue code, marshaling data back and forth between the view (or the user that’s seeing it) and the data storage.

At their most basic, controllers and models are simply classes that have a specific job. They are not the only class types that you can use, obviously, but they make up the core of how this framework is designed to be used. They even have designated directories in the app directory for their storage, though you’re free to store them wherever you desire, as long as they are properly namespaced. We will discuss that in more detail below.


2. Installation Guide 

PHP Version >= 7.4.x

2.1 By Composer
run cmd: git clone https://github.com/Aakash0918/code-taskfrom
Upload database sql file(code_test.sql) and establish connection in .env file
goto project root directory open CMD
run cmd: composer update
then run cmd: php spark serve

2.2 Manual
Deploy project zip file and extract it.
Upload database sql file(code_test.sql) and establish connection in .env file
set base_url path in env. as well as postman
run your server

3. API request response documentation

Postman Collection API URL: https://api.postman.com/collections/10359866-45174c13-798d-4e2e-bf36-10dc6276a9df?access_key=PMAT-01H5S747ANW7FBXD25A6EH7QAC

In Postman Collection their two collection variable BASEURL=>localhost:8080 AND token=>eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoiMSIsInJvbGUiOiJ3cml0ZXIiLCJpYXQiOjE2ODk4MzUyOTMsImV4cCI6MTY5MjQyNzI5M30.a9FwsnXErycp07N1xF_Bxjkde9OUEQWh1HcKYRfPoFM

Kindly change as per your Environment.

General Status Code
1. 405 => Request method not Allowed (Try to access post by get method)

{
    "status": false,
    "message": "The requested action is not allowed."
}

2. 400 => Formerror or Url parameter Error

{
    "status": false,
    "message": "Validation error occurs.",
    "formErrors": {
        "email": "Account does not exits."
    }
}

3. 429 => Throttler(In 1 minute accept 60 request from single IP)

{
    "status": false,
    "message": "Too many request",
}

4. 401 => Token Error

{
    "status": false,
    "message": "Access Denied.",
}

5. 403 => Access forbidden(Occurs when editor try to access writer node points)

{
    "status": false,
    "message": "Access frobidden.",
}

6. 500 => Internal server error occurs. (Contact to support)

{
    "status": false,
    "message": "Internal server error occurs.",
}

7. 200 => on Successfully 

{
    "status": true,
    "message": "Login successful.",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoiMSIsInJvbGUiOiJ3cml0ZXIiLCJpYXQiOjE2ODk4NDMzMzEsImV4cCI6MTY5MjQzNTMzMX0.ed7PB9RoKqv9gSjTzQaBIf6r7ku05TN58EzUyffnqX0",
        "detail": {
            "mobile": "9876543210",
            "role": "writer",
            "email": "aakash@gmail.co"
        }
    }
}






