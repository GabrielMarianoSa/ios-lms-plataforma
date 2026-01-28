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
- `IOS_DB_USER`
- `IOS_DB_PASS`
- `IOS_DB_NAME`

Compatibilidade: se o Railway expor variáveis no padrão `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, o projeto também reconhece.

## Extras (opcionais)

### Dados adicionais do curso (datas/local/modalidade)

Para deixar a página de cursos 100% dinâmica com datas, local, modalidade, turno e vagas **sem alterar tabelas existentes**, você pode criar uma tabela opcional `curso_infos`.

- SQL: `sql/curso_infos.sql` (execute no banco `ios`)
- Tela admin para editar: `admin/curso_detalhes.php?curso_id=ID`

## Diferenciais

Projeto desenvolvido com foco em ambiente corporativo, integrações e plataformas educacionais.

Autor: Gabriel
