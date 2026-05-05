CREATE DATABASE IF NOT EXISTS coderush CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE coderush;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login_id VARCHAR(100) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    ruolo ENUM('host', 'studente') NOT NULL
);

CREATE TABLE IF NOT EXISTS classi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anno TINYINT NOT NULL,
    sezione VARCHAR(2) NOT NULL,
    indirizzo VARCHAR(100) NOT NULL,
    UNIQUE KEY uk_classe (anno, sezione, indirizzo)
);

CREATE TABLE IF NOT EXISTS studente_classe (
    studente_id INT NOT NULL,
    classe_id INT NOT NULL,
    PRIMARY KEY (studente_id, classe_id),
    FOREIGN KEY (studente_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (classe_id) REFERENCES classi(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS linguaggi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS domande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    testo TEXT NOT NULL,
    linguaggio_id INT NOT NULL,
    difficolta TINYINT(1) NULL,
    host_id INT NOT NULL,
    FOREIGN KEY (linguaggio_id) REFERENCES linguaggi(id),
    FOREIGN KEY (host_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS partite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    host_id INT NOT NULL,
    classe_id INT NOT NULL,
    domanda_id INT NOT NULL,
    tempo_lettura INT NOT NULL,
    tempo_turno INT NOT NULL,
    stato ENUM('attesa', 'lettura', 'scrittura', 'finita') NOT NULL DEFAULT 'attesa',
    round_corrente INT NOT NULL DEFAULT 0,
    fase_inizio DATETIME NULL,
    codice_accesso VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(id),
    FOREIGN KEY (classe_id) REFERENCES classi(id),
    FOREIGN KEY (domanda_id) REFERENCES domande(id)
);

CREATE TABLE IF NOT EXISTS partecipazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partita_id INT NOT NULL,
    studente_id INT NOT NULL,
    slot_number INT NOT NULL,
    UNIQUE KEY uk_studente (partita_id, studente_id),
    UNIQUE KEY uk_slot (partita_id, slot_number),
    FOREIGN KEY (partita_id) REFERENCES partite(id) ON DELETE CASCADE,
    FOREIGN KEY (studente_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS turni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partita_id INT NOT NULL,
    studente_id INT NOT NULL,
    slot_id INT NOT NULL,
    numero_turno INT NOT NULL,
    codice TEXT NULL,
    submitted_at DATETIME NULL,
    UNIQUE KEY uk_turno (partita_id, studente_id, numero_turno),
    FOREIGN KEY (partita_id) REFERENCES partite(id) ON DELETE CASCADE,
    FOREIGN KEY (studente_id) REFERENCES users(id),
    FOREIGN KEY (slot_id) REFERENCES partecipazioni(id)
);

CREATE TABLE IF NOT EXISTS valutazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_id INT NOT NULL UNIQUE,
    voto ENUM('corretto', 'parziale', 'sbagliato') NOT NULL,
    feedback TEXT NOT NULL,
    FOREIGN KEY (slot_id) REFERENCES partecipazioni(id) ON DELETE CASCADE
);

INSERT IGNORE INTO linguaggi (nome) VALUES
('Python'), ('JavaScript'), ('Java'), ('C'), ('C++'), ('PHP'), ('SQL'), ('HTML/CSS');
