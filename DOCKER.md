# Docker Compose - Certificados 2017

## 🚀 Quick Start

### 1. Prepare environment
```bash
cp .env.example .env
# Edit .env with your settings
```

### 2. Build and start containers
```bash
docker-compose up -d
```

### 3. Install dependencies (if not auto-installed)
```bash
docker-compose exec app composer install
```

### 4. Generate Doctrine entities
```bash
docker-compose exec app php generate-entities.bat
```

## 📱 Acesso

- **App**: http://localhost:8080
- **PhpMyAdmin**: http://localhost:8081
- **Database**: localhost:3306

## 🛠️ Comandos úteis

### View logs
```bash
docker-compose logs -f app
docker-compose logs -f db
```

### Access container shell
```bash
docker-compose exec app bash
docker-compose exec db bash
```

### Stop containers
```bash
docker-compose down
```

### Remove volumes (⚠️ deletes database)
```bash
docker-compose down -v
```

### Rebuild image
```bash
docker-compose build --no-cache
```

## 🔧 Configuration

Edite as variáveis de ambiente no `.env`:
```env
DB_USER=root
DB_PASSWORD=root
DB_NAME=certificados
```

## 📝 Notes

- O volume `./` monta o projeto inteiro para desenvolvimento
- PhpMyAdmin já incluso para gerenciamento da BD
- Apache com mod_rewrite habilitado
- PHP 7.4 com extensões necessárias
