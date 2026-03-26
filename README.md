# Challenge D

**Challenge D** é um sistema de gerenciamento de fazendas e rebanho bovino.
Ele permite cadastrar fazendas, animais e veterinários, controlar abate, gerar relatórios de produção de leite e consumo de ração, além de exportar dados em CSV.

---

## 🚀 Tecnologias Utilizadas

- [PHP 8.2](https://www.php.net/)
- [Symfony 7.2](https://symfony.com/) — framework web full-stack
- [MySQL 8.0](https://www.mysql.com/) — banco de dados relacional
- [Doctrine ORM 3.6](https://www.doctrine-project.org/) — ORM e migrações
- [KnpPaginator](https://github.com/KnpLabs/KnpPaginatorBundle) — paginação de listagens
- [Stimulus](https://stimulus.hotwired.dev/) & [Turbo](https://turbo.hotwired.dev/) — interatividade no frontend
- [Bootstrap](https://getbootstrap.com/) — estilização
- [Docker](https://www.docker.com/) & [Docker Compose](https://docs.docker.com/compose/) — containerização e orquestração
- [PHPUnit 11.5](https://phpunit.de/) — framework de testes
- [Nginx](https://nginx.org/) — servidor web

---

## 💻 Pré-requisitos

Antes de começar, verifique se você atende aos seguintes requisitos:

- 🐋 Docker

- 🚪 Portas: 8080 (web), 3306 (mysql).

---

## 🧰 Variáveis de ambiente

Normalmente, não é considerado uma boa prática versionar ou expor o arquivo .env em repositórios, já que ele pode conter informações sensíveis como credenciais, chaves de API e configurações privadas.

No entanto, para fins exclusivamente práticos e de aprendizado, o arquivo .env já está incluído neste repositório. Isso facilita a execução imediata do projeto sem a necessidade de configurações adicionais, uma vez que não se trata de um projeto real em produção.

| Variável              | Descrição                                  |
|-----------------------|--------------------------------------------|
| `APP_ENV`             | Ambiente da aplicação (dev, test, prod)    |
| `APP_SECRET`          | Chave secreta do Symfony                   |
| `MYSQL_ROOT_PASSWORD` | Senha root do MySQL                        |
| `MYSQL_DATABASE`      | Nome do banco de dados                     |
| `MYSQL_USER`          | Usuário do banco de dados                  |
| `MYSQL_PASSWORD`      | Senha do usuário do banco                  |
| `DATABASE_URL`        | URL de conexão com o banco (gerada a partir das variáveis acima) |

---

## 🔧 Como rodar o projeto

1. Clone este repositório:
```bash
git clone <url-do-repositorio>
```

2. Verifique se o arquivo .env está presente na raiz do projeto (mesma pasta que o docker-compose.yml). Caso não esteja, crie um com base no exemplo:

```bash
APP_ENV=dev
APP_SECRET=1bac9c532d10d071880ccca2c0f48859
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=challange_d
MYSQL_USER=app
MYSQL_PASSWORD=app
DATABASE_URL="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@mysql:3306/${MYSQL_DATABASE}?serverVersion=8.0&charset=utf8mb4"
```

3. Suba os containers:

```bash
make up
```

ou

```bash
docker compose up -d
```

As dependências são instaladas e as migrações executadas automaticamente na inicialização do container.

4. Acesse a aplicação em [http://localhost:8080](http://localhost:8080).

---

## ⚙️ Backend

- CRUD completo de fazendas, animais e veterinários com paginação e filtros
- Controle de abate e reversão com validações de capacidade e código único
- Dashboard com indicadores de produção de leite, consumo de ração e rankings
- Relatórios com exportação em CSV
- Validadores customizados para capacidade da fazenda e unicidade de código entre animais vivos

---

## 🎨 Frontend

A interface foi construída com Bootstrap e conta com:

- 🌗 **Tema claro e escuro** — alternável pelo botão na navbar, com preferência salva no navegador
- 📄 **Paginação** — listagens paginadas via KnpPaginator
- 🔍 **Filtros** — busca e filtragem por nome, status, fazenda, faixas de peso/leite/ração
- 📊 **Relatórios** — visualização com filtros e exportação em CSV

---

## 🌱 Seeds e Reset

O projeto conta com uma ferramenta de desenvolvimento para facilitar testes e validação, já que o objetivo do projeto é prático. Acessível em [http://localhost:8080/dev](http://localhost:8080/dev) e também pela navbar quando `APP_ENV=dev`.

1. **Seed** — Popula o banco com veterinários, fazendas e animais de exemplo.
2. **Reset** — Limpa todas as tabelas, permitindo recomeçar do zero.

---

## 🧪 Testes

O projeto conta com testes automatizados organizados em 4 suítes:

- **Unit** — Testes de entidades e regras de negócio
- **Integration** — Testes de repositórios e validadores com banco de dados real
- **Functional** — Testes dos controllers via requisições HTTP
- **EndToEnd** — Teste do fluxo completo da aplicação

Para isolar o ambiente de desenvolvimento, os testes utilizam um `docker-compose.test.yml` próprio com uma instância MySQL separada em `tmpfs`, garantindo um banco limpo e descartável a cada execução.

Para rodar os testes:

```bash
make test
```
