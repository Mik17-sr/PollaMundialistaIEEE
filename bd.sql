USE polla_db;
CREATE TABLE usuario (
  id        INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(150) NOT NULL,
  codigo    VARCHAR(20)  NOT NULL UNIQUE,
  correo    VARCHAR(150) NOT NULL,
  telefono  VARCHAR(20),
  es_ud   TINYINT(1) NOT NULL DEFAULT 1,
  proyecto  VARCHAR(150)
);

CREATE TABLE predicciones (
  id          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_usuario  INT(11)      NOT NULL,
  tipo        ENUM('free','3000') NOT NULL,
  estado      ENUM('activa','pendiente') NOT NULL DEFAULT 'activa',
  comprobante VARCHAR(300) NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id)
);

CREATE TABLE podio (
  id            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_prediccion INT(11)      NOT NULL,
  campeon       VARCHAR(100),
  subcampeon    VARCHAR(100),
  tercero       VARCHAR(100),
  cuarto        VARCHAR(100),
  FOREIGN KEY (id_prediccion) REFERENCES predicciones(id)
);

CREATE TABLE grupos (
  id            INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_prediccion INT(11)     NOT NULL,
  grupo         CHAR(1)     NOT NULL,
  primero       VARCHAR(100),
  segundo       VARCHAR(100),
  tercero       VARCHAR(100),
  cuarto        VARCHAR(100),
  FOREIGN KEY (id_prediccion) REFERENCES predicciones(id)
);

CREATE TABLE terceros (
  id            INT(11)  NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_prediccion INT(11)  NOT NULL,
  grupo         CHAR(1)  NOT NULL,
  FOREIGN KEY (id_prediccion) REFERENCES predicciones(id)
);

CREATE TABLE eliminatorias (
  id            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_prediccion INT(11)      NOT NULL,
  ronda         VARCHAR(10)  NOT NULL,
  partido_id    VARCHAR(20)  NOT NULL,
  equipo1       VARCHAR(100),
  equipo2       VARCHAR(100),
  ganador       VARCHAR(100),
  FOREIGN KEY (id_prediccion) REFERENCES predicciones(id)
);

CREATE TABLE desempate (
  id             INT(11)  NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_prediccion  INT(11)  NOT NULL,
  goleador       VARCHAR(100),
  mejor_arquero  VARCHAR(100),
  goles_final    INT(11),
  tarjetas_rojas INT(11),
  goles_grupos   INT(11),
  FOREIGN KEY (id_prediccion) REFERENCES predicciones(id)
);

CREATE TABLE preguntas_extra (
  id                INT(11)        NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_prediccion     INT(11)        NOT NULL,
  equipo_sorpresa   VARCHAR(100),
  equipo_decepcion  VARCHAR(100),
  jugador_joven     VARCHAR(100),
  seleccion_goles   VARCHAR(100),
  seleccion_defensa VARCHAR(100),
  prorroga_final    ENUM('si','no') NULL,
  FOREIGN KEY (id_prediccion) REFERENCES predicciones(id)
);

CREATE TABLE real_podio (
  id         INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  campeon    VARCHAR(100),
  subcampeon VARCHAR(100),
  tercero    VARCHAR(100),
  cuarto     VARCHAR(100)
);

CREATE TABLE real_grupos (
  id      INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  grupo   CHAR(1)      NOT NULL UNIQUE,
  primero VARCHAR(100),
  segundo VARCHAR(100),
  tercero VARCHAR(100),
  cuarto  VARCHAR(100)
);

CREATE TABLE real_terceros (
  id    INT(11)  NOT NULL AUTO_INCREMENT PRIMARY KEY,
  grupo CHAR(1)  NOT NULL
);

CREATE TABLE real_eliminatorias (
  id         INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ronda      VARCHAR(10)  NOT NULL,
  partido_id VARCHAR(20)  NOT NULL UNIQUE,
  equipo1    VARCHAR(100),
  equipo2    VARCHAR(100),
  ganador    VARCHAR(100)
);

CREATE TABLE real_desempate (
  id             INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  goleador       VARCHAR(100),
  mejor_arquero  VARCHAR(100),
  goles_final    INT(11),
  tarjetas_rojas INT(11),
  goles_grupos   INT(11)
);

CREATE TABLE real_preguntas_extra (
  id                INT(11)        NOT NULL AUTO_INCREMENT PRIMARY KEY,
  equipo_sorpresa   VARCHAR(100),
  equipo_decepcion  VARCHAR(100),
  jugador_joven     VARCHAR(100),
  seleccion_goles   VARCHAR(100),
  seleccion_defensa VARCHAR(100),
  prorroga_final    ENUM('si','no') NULL
);
CREATE TABLE admins (
  id            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  usuario       VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admins (nombre, usuario, password_hash) VALUES (
  'ieeetecno',
  'ieeetecno',
  '$2y$10$eXpyNEI6kHHmbeO2/R6cSux1UQ.w0JaWfizgHL1OZy/tsIG0G0Xey'
);