# FYP-Submission-Management-System
HAIII 

This project is a web-based platform designed to manage Final Year Project (FYP) submissions in a university.

Students will use the system to upload their project materials such as proposals, reports, source code, posters, slides, and possibly GitHub links. Lecturers will then review and grade these submissions based on the students assigned to them.

Programming Language	HTML, CSS, JavaScript, PHP
Framework	Bootstrap
Database	PostgreSQL

## PostgreSQL setup

Install/enable the PHP PostgreSQL PDO extension (`pdo_pgsql`), create a database, then configure:

```bash
export DB_CONNECTION=pgsql
export DB_HOST=127.0.0.1
export DB_PORT=5432
export DB_DATABASE=fyp_submission_system
export DB_USERNAME=postgres
export DB_PASSWORD=Nigaman00
```
Initialize the schema and starter encrypted accounts:

```bash
php init_db.php
```

Start psotgre
sudo service postgresql start

Start php
php -S 0.0.0.0:8000

Theme: Universiti Teknologi Malaysia colour theme
UTM Maroon: #800020
UTM Gold:   #F2A900
White:      #FFFFFF
Dark Text:  #222222

blablablabla