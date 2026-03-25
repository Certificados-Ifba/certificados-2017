Certificados
=======================

Instrodução
------------
Aplicação de gerenciamento de certificados


Instalação 
---------------------------

Para rodar o projeto:

1. Instalar as dependências: 


`cd path/to/install`

`composer update`
    
 2. Criar banco de dados com os arquivos da pasta bd/sql
 
 3. Alterar dados de usuário do banco no arquivo config/doctrine.local.php


Requerimentos
---------------------------

PHP 5.3.23 or later; we recommend using the latest PHP version whenever possible


Docker
---------------------------

O projeto foi dockerizado com:

- `app`: PHP 7.4 + Apache (DocumentRoot em `public_html`)
- `db`: MySQL 5.7

### Subir os containers

1. Ajuste os valores de banco no arquivo `.env` (já com padrão para Docker).
2. Execute:

`docker compose up -d --build`

3. Acesse:

`http://localhost:8081`

### Banco de dados

- Host da aplicação: `db`
- Porta interna: `3306`
- Porta externa (host): `3307`

Se houver dump SQL, você pode importar manualmente ou descomentar no `docker-compose.yml` o volume:

`./bd/sql:/docker-entrypoint-initdb.d:ro`

### Arquivos criados para Docker

- `Dockerfile`
- `docker-compose.yml`
- `docker/apache/000-default.conf`
- `.dockerignore`
- `config/autoload/doctrine.local.php`


