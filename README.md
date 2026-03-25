Certificados
=======================

Introducao
------------
Aplicacao de gerenciamento de certificados.

Rodando com Docker
---------------------------

### 1) Subir ambiente local (app + mysql)

```bash
docker compose up --build -d
```

Aplicacao: `http://localhost:8080`

### 2) Variaveis de ambiente

As variaveis de banco sao lidas do arquivo `.env` (ou variaveis do ambiente no Coolify):

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`

O arquivo `config/autoload/doctrine.global.php` ja esta preparado para usar essas variaveis.

### 3) Importar estrutura inicial do banco

Importe os scripts SQL da pasta `bd/sql` no banco configurado.

Deploy no Coolify
---------------------------

Use uma aplicacao do tipo Dockerfile:

1. Aponte para este repositório.
2. Build Pack: `Dockerfile`.
3. Porta exposta da aplicacao: `80`.
4. Configure variaveis de ambiente de banco (`DB_*`).
5. Se usar banco gerenciado pelo Coolify, use hostname/porta fornecidos por ele.

Requisitos
---------------------------

- Docker 24+
- Docker Compose v2
