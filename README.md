# Deploy (Railway) + Segurança

## Variáveis de ambiente (obrigatórias em produção)

### Banco de dados

Este projeto lê credenciais do banco por ENV. Em Railway (MySQL plugin), normalmente já vem:

- `MYSQLHOST`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQLDATABASE`
- `MYSQLPORT`

Alternativamente, você pode definir os aliases usados pelo app:

- `IOS_DB_HOST`
- `IOS_DB_USER`
- `IOS_DB_PASS`
- `IOS_DB_NAME`
- `IOS_DB_PORT`

### Chat IA (Groq)

- `GROQ_API_KEY` (NUNCA colocar no código / Git)

Se `GROQ_API_KEY` não estiver definida, o chat IA retorna `503` com mensagem de configuração.

## Arquivo .env (apenas local)

Para rodar local sem expor segredos, você pode criar um arquivo `.env` na raiz do projeto (ele já está no `.gitignore`).
O [partials/bootstrap.php](partials/bootstrap.php) faz o carregamento automático do `.env` (sem sobrescrever envs já existentes).

Exemplo `.env`:

```
GROQ_API_KEY=coloque_sua_chave_aqui
```

## Deploy via GitHub (recomendado)

1. Suba o repo para o GitHub.
2. No Railway: **New Project** → **Deploy from GitHub Repo**.
3. Em **Settings** do serviço:

- Builder: **Dockerfile** (o projeto já inclui `Dockerfile`).

4. Adicione o plugin **MySQL** no projeto (ou use um banco externo).
5. Em **Variables**:

- Garanta `GROQ_API_KEY` configurada.

6. Deploy.

Observação: o deploy usa [router.php](router.php) para bloquear acesso direto a arquivos sensíveis (ex.: dumps `.sql`, `config/`, `docs/`).

## Deploy via Railway CLI

Você precisa autenticar no seu PC (eu não consigo logar na sua conta).

1. Instale a CLI:

- `npm i -g @railway/cli`

2. Login:

- `railway login`

3. Dentro do projeto:

- `railway link`
- `railway up`

## IMPORTANTE: Rotacione a chave Groq

Se você já chegou a colocar uma chave no código em algum momento, trate como comprometida:

1. Gere uma nova chave no painel da Groq.
2. Atualize `GROQ_API_KEY` no Railway.
3. Pare de usar a chave antiga.

# Plataforma IOS – Sistema de Cursos e LMS

Sistema web desenvolvido em PHP para gerenciamento de cursos, alunos e aulas, inspirado em plataformas LMS como Moodle.

## Funcionalidades

- Autenticação (Admin / Aluno)
- Painel administrativo
- CRUD de cursos
- Sistema de inscrições
- Visualização de inscritos
- LMS com aulas e progresso do aluno
- Porcentagem de progresso por curso
- Integração simulada com:
  - RD Station (CRM)
  - Protheus (ERP)
- Banco de dados relacional com MySQL
- Arquitetura pronta para cloud

## Tecnologias

- PHP 8
- MySQL
- HTML5 / CSS3 / JavaScript
- Bootstrap 5
- SQL (JOINs, relacionamentos)
- API REST simulada

## Estrutura

- /admin – painel administrativo
- /auth – autenticação
- /api – integrações
- LMS – módulo de aulas e progresso

## Como executar

1. Instalar Laragon/XAMPP
2. Criar banco `ios`
3. Importar tabelas
4. Rodar em `http://localhost/ios`

## Deploy (Railway)

Configure as variáveis de ambiente do banco (MySQL/MariaDB) no Railway:

- `IOS_DB_HOST`
- `IOS_DB_PORT` (opcional, padrão 3306)
- `IOS_DB_USER`
- `IOS_DB_PASS`
- `IOS_DB_NAME`

Alternativa (URL única): `IOS_DB_URL` (ex: `mysql://user:pass@host:3306/database`)

Compatibilidade: se o Railway expor variáveis no padrão `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, o projeto também reconhece.

Porta: também reconhece `MYSQLPORT`. Se existir `DATABASE_URL`/`MYSQL_URL`, o projeto tenta parsear automaticamente.

## Extras (opcionais)

### Dados adicionais do curso (datas/local/modalidade)

Para deixar a página de cursos 100% dinâmica com datas, local, modalidade, turno e vagas **sem alterar tabelas existentes**, você pode criar uma tabela opcional `curso_infos`.

- SQL: `sql/curso_infos.sql` (execute no banco `ios`)
- Tela admin para editar: `admin/curso_detalhes.php?curso_id=ID`

## Diferenciais

Projeto desenvolvido com foco em ambiente corporativo, integrações e plataformas educacionais.

Autor: Gabriel
