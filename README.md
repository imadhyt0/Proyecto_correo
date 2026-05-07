# Servidor de Correo - imad.local


---

## ¿De qué va esto?

He montado un servidor de correo completo en una Debian 13 desde cero. El dominio es `imad.local` y la IP del servidor es `192.168.75.177`. Aquí explico todo lo que he instalado y cómo lo he configurado.

---

## Lo que he instalado

### Postfix
El MTA, el que envía y recibe correos entre servidores. Lo primero que hay que instalar.

```bash
sudo apt install postfix -y
```

Al instalarlo pide el tipo de configuración, hay que elegir "Sitio de Internet" y poner tu dominio en mi caso `imad.local`. Lo más importante que he tocado en `main.cf` es el formato de buzón:

```
home_mailbox = Maildir/
```

### Dovecot
El MDA, guarda los correos y deja que los clientes los lean por IMAP o POP3.

```bash
sudo apt install dovecot-core dovecot-imapd dovecot-pop3d -y
```

**Problema que tuve:** Dovecot 2.4 (la versión de Debian 13) ha cambiado un montón de cosas respecto a versiones anteriores. El archivo principal tiene que empezar con `dovecot_config_version = 2.4.1` sino no arranca. También cambiaron nombres de parámetros, por ejemplo `mail_location` ahora es `mail_driver` y `mail_path`, y `ssl_cert` ahora es `ssl_server_cert_file`. Me tiré un buen rato con esto.

### Maildir
Formato en el que se guardan los correos. Cada correo es un archivo independiente, mucho mejor que mbox que lo mete todo junto. Los buzones están en `/home/imad/Maildir/` y `/home/susana/Maildir/`.

```bash
sudo maildirmake.dovecot /home/imad/Maildir
sudo maildirmake.dovecot /home/susana/Maildir
sudo chown -R imad:imad /home/imad/Maildir
sudo chown -R susana:susana /home/susana/Maildir
```

### Seguridad (Amavis + ClamAV + SpamAssassin)
Amavis coge los correos antes de que lleguen al buzón y los manda a analizar. ClamAV busca virus y SpamAssassin puntúa si es spam (más de 5 puntos = spam).

```bash
sudo apt install amavisd-new spamassassin clamav clamav-daemon -y
```

El flujo queda así:
```
Postfix (25) → Amavis (10024) → ClamAV + SpamAssassin → Postfix (10025) → buzón
```

### TLS/SSL
He creado una CA propia y con ella he firmado el certificado del servidor. No he usado el certificado autofirmado que viene por defecto (snakeoil) porque no es correcto usarlo.

```bash
sudo mkdir -p /etc/ssl/CA
sudo openssl genrsa -out /etc/ssl/CA/ca.key 4096
sudo openssl req -new -x509 -days 3650 -key /etc/ssl/CA/ca.key -out /etc/ssl/CA/ca.crt \
  -subj "/C=ES/ST=Andalucia/L=Granada/O=IES Iliberis/OU=CA/CN=CA imad.local"
sudo openssl genrsa -out /etc/ssl/private/mail.imad.local.key 4096
sudo openssl req -new -key /etc/ssl/private/mail.imad.local.key \
  -out /etc/ssl/CA/mail.imad.local.csr \
  -subj "/C=ES/ST=Andalucia/L=Granada/O=IES Iliberis/OU=Correo/CN=mail.imad.local"
sudo openssl x509 -req -days 1095 \
  -in /etc/ssl/CA/mail.imad.local.csr \
  -CA /etc/ssl/CA/ca.crt -CAkey /etc/ssl/CA/ca.key -CAcreateserial \
  -out /etc/ssl/certs/mail.imad.local.crt
```

### Roundcube
Cliente web de correo, se accede desde el navegador. Necesita Apache, PHP y MariaDB.

```bash
sudo apt install roundcube roundcube-mysql -y
```

Acceso: `http://192.168.75.177/roundcube`

### Thunderbird
Cliente de escritorio. Lo he configurado con IMAP en el puerto 143 y SMTP en el puerto 25.

```bash
sudo apt install thunderbird -y
```

### PGP
La firma digital y el cifrado se configura en Thunderbird, no en el servidor. Cada usuario genera sus claves desde `Configuración de cuenta → Cifrado de extremo a extremo`.

### Fetchmail
Recoge correos de cuentas externas. Lo he configurado con mi Gmail personal.

```bash
sudo apt install fetchmail -y
```

Configuración en `/root/.fetchmailrc`:
```
poll pop.gmail.com
    proto POP3
    port 995
    user "imadhyt0@gmail.com"
    password "contraseña_aplicacion"
    ssl
    mda "/usr/sbin/sendmail -i imad@imad.local"
    fetchlimit 5
```

**Problema que tuve:** La primera vez no puse `fetchlimit` y con 324 mensajes en el Gmail me congeló la máquina entera y tuve que reiniciar.

---
