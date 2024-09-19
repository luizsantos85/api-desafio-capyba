<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

## PROJETO DESAFIO CAPYBA


## Configurações / Requisitos

-   **[nginx:alpine]**
-   **[mysql:8.0](https://www.mysql.com/)**
-   **[redis]**
-   **[Laravel:10](https://laravel.com/)**
-   **[PHP:8.1\*](https://www.php.net/manual/pt_BR/index.php)**
-   **[L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger)**
-   **[docker]**

## Instalar App Usando docker

```bash
$ git clone https://github.com/luizsantos85/api-desafio-capyba.git

**observar as configurações de portas e usuario no arquivo docker-composer.yml

**Copiar o .env.example e gerar o .env, fazer as modificações das portas (se necessário) e usuario do DB

**Inicializar os containers
$ docker compose up -d

**será criada uma pasta .docker/mysql para os arquivos de banco de dados

**Acessar o container do laravel
$ docker compose exec app bash

**Instalar os packges do laravel
$ composer install

**Gerar a key do laravel
$ php artisan key:generate

**Gerar as migrations do banco
$ php artisan migrate

**acessar localhost:(porta selecionada) para acessar o sistema
**acessar documentação api - localhost:(porta selecionada)/api/documentation/
```

## Instalar App diretamente na maquina

```bash
$ git clone https://github.com/luizsantos85/api-desafio-capyba.git

**Necessário ter o composer instalado

**Copiar o .env.example e gerar o .env, fazer as modificações das portas (se necessário) e usuario do DB

**Instalar os packges do laravel
$ composer install

**Gerar a key do laravel
$ php artisan key:generate

**Criar o banco de dados no mysql (api-capyba)
**Gerar as migrations do banco
$ php artisan migrate


**acessar localhost:(porta selecionada) para acessar o sistema
```
