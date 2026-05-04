CREATE DATABASE IF NOT EXISTS dopamineDB
  CHARACTER SET utf8mb4;

USE dopamineDB;

/* 
Tabla de usuario, energia es un campo necesario para modificar
*/
CREATE TABLE user (
    id           INT          AUTO_INCREMENT PRIMARY KEY,
    nickName     VARCHAR(30)  NOT NULL,
    email        VARCHAR(50) UNIQUE NOT NULL,
    pswd         VARCHAR(200) NOT NULL,
    energy       ENUM('low','medium','high') DEFAULT 'medium'
);


CREATE TABLE task (
    id           INT          AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    title        VARCHAR(30)  NOT NULL,
    descrip      VARCHAR(100),
    startDate    DATETIME     DEFAULT NULL,
    difficulty   ENUM('facil','media','dificil') DEFAULT 'media',
    done         BOOLEAN      DEFAULT FALSE,
    createdDate  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    expDate      DATETIME     NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


CREATE TABLE habit (
    id          INT         AUTO_INCREMENT PRIMARY KEY,
    user_id     INT         NOT NULL,
    title       VARCHAR(30) NOT NULL,
    descrip     VARCHAR(100),
    icon        VARCHAR(10),
    frecuency   ENUM('diaria','semanal','mensual') DEFAULT 'diaria',
    dayOfMonth  TINYINT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


CREATE TABLE routine (
    id         INT         AUTO_INCREMENT PRIMARY KEY,
    user_id INT         NOT NULL,
    title     VARCHAR(30) NOT NULL,
    descrip VARCHAR(100),
    frecuency ENUM('dialy','weekly','monthly') DEFAULT 'dialy',
    dayOfMonth TINYINT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE task_checklist (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    task_id    INT          NOT NULL,
    title      VARCHAR(30) NOT NULL,
    done       BOOLEAN      DEFAULT FALSE,
    sort_order INT          DEFAULT 0,
    FOREIGN KEY (task_id) REFERENCES task(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE routine_checklist (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    routine_id  INT          NOT NULL,
    title       VARCHAR(30) NOT NULL,
    done        BOOLEAN      DEFAULT FALSE,
    sort_order  INT          DEFAULT 0,
    FOREIGN KEY (routine_id) REFERENCES routine(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


CREATE TABLE routine_habit (
    routine_id INT NOT NULL,
    habit_id   INT NOT NULL,
    sort_order INT DEFAULT 0,
    PRIMARY KEY (routine_id, habit_id),
    FOREIGN KEY (routine_id) REFERENCES routine(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (habit_id) REFERENCES habit(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE habit_day (
    habit_id INT NOT NULL,
    dayOfWeek  ENUM('lunes','martes','miercoles','jueves',
               'viernes','sabado','domingo') NOT NULL,
    PRIMARY KEY (habit_id, dayOfWeek),
    FOREIGN KEY (habit_id) REFERENCES habit(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE routine_day (
    routine_id INT NOT NULL,
    dayOfWeek      ENUM('lunes','martes','miercoles','jueves',
                   'viernes','sabado','domingo') NOT NULL,
    PRIMARY KEY (routine_id, dayOfWeek),
    FOREIGN KEY (routine_id) REFERENCES routine(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


CREATE TABLE habit_record (
    id          INT     AUTO_INCREMENT PRIMARY KEY,
    habit_id    INT     NOT NULL,
    dateOfHabit DATE    NOT NULL,
    done        BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (habit_id) REFERENCES habit(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


CREATE TABLE routine_record (
    id             INT     AUTO_INCREMENT PRIMARY KEY,
    routine_id     INT     NOT NULL,
    dateOfRoutine  DATE    NOT NULL,
    totalSubtasks  TINYINT NOT NULL,
    doneSubtasks   TINYINT NOT NULL,
    done           BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (routine_id) REFERENCES routine(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


CREATE TABLE task_record (
    id        INT      AUTO_INCREMENT PRIMARY KEY,
    task_id   INT      NOT NULL,
    doneDate  DATETIME DEFAULT CURRENT_TIMESTAMP,
    onTime    BOOLEAN  DEFAULT TRUE,
    FOREIGN KEY (task_id) REFERENCES task(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);