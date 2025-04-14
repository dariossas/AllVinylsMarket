CREATE DATABASE AllVinylsMarket;
USE AllVinylsMarket;

CREATE TABLE UTENTI (
    id_utente INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(20),
    cognome VARCHAR(20),
    username VARCHAR(50), 
    paese ENUM('Italia'),
    regione ENUM('Abruzzo', 'Basilicata', 'Calabria', 'Campania', 'Emilia-Romagna', 'Friuli Venezia Giulia', 'Lazio', 'Liguria', 'Lombardia', 'Marche', 'Molise', 'Piemonte', 'Puglia', 'Sardegna', 'Sicilia', 'Toscana', 'Trentino-Alto Adige', 'Umbria', 'Valle d_Aosta', 'Veneto'),
    password_utente VARCHAR(100),
    email VARCHAR(255),
    immagine_profilo VARCHAR(255), -- memorizza il percorso che fa l'immagine
    PRIMARY KEY (id_utente) 
);

CREATE TABLE ANNUNCI (
    id_annuncio INT NOT NULL AUTO_INCREMENT,
    id_utente INT NOT NULL,
    titolo VARCHAR(255),
    descrizione VARCHAR(600),
    prezzo DECIMAL(5,2), -- 5 cifre totali due dopo la virgola
    condizioni ENUM('Nuovo_con_pellicola', 'Nuovo', 'Buone condizioni', 'Usato', 'Molto Usato'),
    data_caricamento DATETIME DEFAULT CURRENT_TIMESTAMP,
    immagine_copertina VARCHAR(255),
    artista VARCHAR(100),
    formato VARCHAR(50),
    PRIMARY KEY (id_annuncio),
    FOREIGN KEY (id_utente) REFERENCES UTENTI(id_utente)
);

CREATE TABLE MESSAGGI ( 
    id_messaggio INT NOT NULL AUTO_INCREMENT,
    contenuto VARCHAR(600),
    data_invio DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_mittente INT NOT NULL,
    id_destinatario INT NOT NULL,
    PRIMARY KEY(id_messaggio),
    FOREIGN KEY (id_mittente) REFERENCES UTENTI(id_utente),
    FOREIGN KEY (id_destinatario) REFERENCES UTENTI(id_utente)
);

CREATE TABLE OFFERTE (
    id_offerta INT NOT NULL AUTO_INCREMENT,
    id_annuncio INT NOT NULL,
    id_offerente INT NOT NULL,
    importo DECIMAL(5,2),
    data_offerta DATETIME DEFAULT CURRENT_TIMESTAMP,
    stato ENUM('In attesa', 'Accettata', 'Rifiutata') DEFAULT 'In attesa',
    PRIMARY KEY(id_offerta),
    FOREIGN KEY (id_annuncio) REFERENCES ANNUNCI(id_annuncio),
    FOREIGN KEY (id_offerente) REFERENCES UTENTI(id_utente)
);

CREATE TABLE CHATS (
    id_chat INT NOT NULL AUTO_INCREMENT,
    id_utente1 INT NOT NULL,
    id_utente2 INT NOT NULL,
    id_annuncio INT NOT NULL,
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id_chat),
    FOREIGN KEY (id_utente1) REFERENCES UTENTI(id_utente),
    FOREIGN KEY (id_utente2) REFERENCES UTENTI(id_utente),
    FOREIGN KEY (id_annuncio) REFERENCES ANNUNCI(id_annuncio)
);

CREATE TABLE LISTA_PREFERITI (
    id_preferito INT NOT NULL AUTO_INCREMENT,
    id_utente INT NOT NULL,
    id_annuncio INT NOT NULL,
    data_aggiunta DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_preferito),
    FOREIGN KEY (id_annuncio) REFERENCES ANNUNCI(id_annuncio),
    FOREIGN KEY (id_utente) REFERENCES UTENTI(id_utente)
);

CREATE TABLE RECENSIONI (
    id_recensione INT NOT NULL AUTO_INCREMENT,
    id_recensore INT NOT NULL,
    id_recensito INT NOT NULL,
    contenuto VARCHAR(600),
    stelle ENUM('1', '2', '3', '4', '5'),
    data_recensione DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id_recensione),
    FOREIGN KEY (id_recensore) REFERENCES UTENTI(id_utente),
    FOREIGN KEY (id_recensito) REFERENCES UTENTI(id_utente)
);