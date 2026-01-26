-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: ios
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `aula_quiz_respostas`
--

DROP TABLE IF EXISTS `aula_quiz_respostas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aula_quiz_respostas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `aula_id` int NOT NULL,
  `q1_resposta` char(1) DEFAULT NULL,
  `q2_resposta` char(1) DEFAULT NULL,
  `acertos` int NOT NULL DEFAULT '0',
  `aprovado` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_aula` (`user_id`,`aula_id`),
  KEY `idx_aula` (`aula_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aula_quiz_respostas`
--

LOCK TABLES `aula_quiz_respostas` WRITE;
/*!40000 ALTER TABLE `aula_quiz_respostas` DISABLE KEYS */;
INSERT INTO `aula_quiz_respostas` VALUES (1,5,8,'B','A',1,0,'2026-01-22 05:13:27','2026-01-22 05:23:09'),(6,5,9,'A','A',2,1,'2026-01-22 05:21:04','2026-01-22 05:21:58'),(9,5,11,'A','A',2,1,'2026-01-26 10:21:09','2026-01-26 10:21:15');
/*!40000 ALTER TABLE `aula_quiz_respostas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aulas`
--

DROP TABLE IF EXISTS `aulas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aulas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `curso_id` int DEFAULT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `conteudo` text,
  `video_url` varchar(255) DEFAULT NULL,
  `pdf` varchar(255) DEFAULT NULL,
  `topico_id` int DEFAULT NULL,
  `ordem` int DEFAULT NULL,
  `q1_pergunta` text,
  `q1_a` varchar(255) DEFAULT NULL,
  `q1_b` varchar(255) DEFAULT NULL,
  `q1_c` varchar(255) DEFAULT NULL,
  `q1_correta` char(1) DEFAULT NULL,
  `q2_pergunta` text,
  `q2_a` varchar(255) DEFAULT NULL,
  `q2_b` varchar(255) DEFAULT NULL,
  `q2_c` varchar(255) DEFAULT NULL,
  `q2_correta` char(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aulas_curso_topico` (`curso_id`,`topico_id`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aulas`
--

LOCK TABLES `aulas` WRITE;
/*!40000 ALTER TABLE `aulas` DISABLE KEYS */;
INSERT INTO `aulas` VALUES (12,19,'Aula 01 - Como tudo começou','Nesta aula, você irá entender o que é o backend e qual o seu papel no desenvolvimento de sistemas.\r\n\r\nVocê aprenderá a diferença entre frontend e backend, como funciona a comunicação entre eles e por que o backend é responsável por regras de negócio, dados e segurança.\r\n\r\nTambém será apresentado às principais tecnologias utilizadas no backend e à estrutura básica de um projeto.\r\n\r\nAo final da aula, você terá uma visão clara de como o backend funciona e do que será desenvolvido ao longo do curso.','https://www.youtube.com/watch?v=Qjk-cSW-jk4','uploads/aulas/1769423563_27ce5ec4d547.pdf',NULL,NULL,'O que é o Back-end?','Parte visual do site','Parte oculta do site','Esqueleto do site','B','Qual desses é uma linguagem de programação Back-end?','HTML','CSS','Python','C'),(13,25,'Aula 01 - Como tudo começou','Nesta aula, você irá entender o que é o backend e qual o seu papel no desenvolvimento de sistemas.\r\n\r\nVocê aprenderá a diferença entre frontend e backend, como funciona a comunicação entre eles e por que o backend é responsável por regras de negócio, dados e segurança.\r\n\r\nTambém será apresentado às principais tecnologias utilizadas no backend e à estrutura básica de um projeto.\r\n\r\nAo final da aula, você terá uma visão clara de como o backend funciona e do que será desenvolvido ao longo do curso.','https://www.youtube.com/watch?v=Qjk-cSW-jk4','uploads/aulas/1769425607_77945aa8bcd1.pdf',NULL,NULL,'O que é o Back-end?','Parte visual do site','Parte oculta do site','Esqueleto do site','B','Qual dessas é uma linguagem de programação Back-end?','HTML','CSS','Python','C'),(14,23,'Aula 01 - Word','Nesta aula você aprenderá sobre o Word, para que serve e como utilizar no dia-a-dia.','https://www.youtube.com/watch?v=_1ls9OeBIvU','uploads/aulas/1769427175_b03e477d5f50.pdf',NULL,NULL,'Para que serve o Word?','Editar texto','Editar vídeo','Editar planilhas','A','Qual é o título principal do Word?','h1','h10','h50','A'),(15,25,'Aula 02 - Avançando em Backend','Nesta aula, você irá aprender os conceitos básicos de lógica de programação aplicados ao backend.\r\n\r\nSerão abordados temas como variáveis, condicionais, laços de repetição e funções, que formam a base para a criação das regras de negócio de um sistema.\r\n\r\nVocê também conhecerá a estrutura básica de um projeto backend, entendendo como os arquivos e pastas são organizados para manter o código limpo e funcional.\r\n\r\nAo final da aula, você estará preparado para começar a desenvolver as primeiras funcionalidades no servidor.','https://www.youtube.com/watch?v=N6vgZr1k03g','uploads/aulas/1769427621_645003f89f77.pdf',NULL,NULL,'O que é servidor?','Estilo do site','Esqueleto do site','Armazenamento do site','C','O que o servidor retorna para o cliente?','Interação','Dados e demais solicitações','Linguagem de programação','B');
/*!40000 ALTER TABLE `aulas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curso_infos`
--

DROP TABLE IF EXISTS `curso_infos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curso_infos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `curso_id` int NOT NULL,
  `modalidade` varchar(50) DEFAULT NULL,
  `local` varchar(120) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `turno` varchar(30) DEFAULT NULL,
  `vagas` int DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curso_infos_curso_id` (`curso_id`),
  KEY `idx_curso_infos_datas` (`data_inicio`,`data_fim`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curso_infos`
--

LOCK TABLES `curso_infos` WRITE;
/*!40000 ALTER TABLE `curso_infos` DISABLE KEYS */;
INSERT INTO `curso_infos` VALUES (1,4,'Híbrido','Santana - SP','2026-02-01','2026-06-01','Manhã',2,'2026-01-21 03:25:00',NULL),(2,6,'Presencial','sao paulo sp','2026-02-01','2026-06-01','Manhã',30,'2026-01-22 03:14:27',NULL),(3,9,'','',NULL,NULL,'',NULL,'2026-01-22 04:20:38','2026-01-22 04:20:42'),(4,11,'Híbrido','sp','2026-01-01','2026-06-01','Tarde',21,'2026-01-22 05:00:58','2026-01-22 05:01:37'),(5,12,'Presencial','sao paulo','2026-01-27','2027-01-01','Manhã',20,'2026-01-26 03:23:51',NULL),(6,16,'Presencial','SP','2026-01-26','2026-01-28','Manhã',20,'2026-01-26 10:14:53',NULL),(7,18,'Híbrido','Av. Ataliba, Santana - SP','2026-01-26','2026-02-28','Manhã',21,'2026-01-26 10:17:24',NULL),(8,19,'Híbrido','Ataliba Leonel, Santana - SP','2026-01-26','2026-01-01','Manhã',29,'2026-01-26 10:28:55','2026-01-26 10:36:37'),(9,22,'Híbrido','Ataliba Leonel, Santana - SP','2026-01-26','2026-01-01','Manhã',29,'2026-01-26 10:39:38',NULL),(10,25,'Híbrido','Ataliba Leonel, Santana - SP','2026-01-26','2026-06-01','Tarde',29,'2026-01-26 11:05:21',NULL),(11,23,'Híbrido','Ataliba Leonel, Santana - SP','2026-04-01','2026-10-01','Manhã',28,'2026-01-26 11:30:19',NULL);
/*!40000 ALTER TABLE `curso_infos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cursos`
--

DROP TABLE IF EXISTS `cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cursos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `descricao` text,
  `carga_horaria` int DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `thumbnail` varchar(255) DEFAULT NULL,
  `pdf` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `modulo_texto` text,
  `inscricoes_abertas` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cursos`
--

LOCK TABLES `cursos` WRITE;
/*!40000 ALTER TABLE `cursos` DISABLE KEYS */;
INSERT INTO `cursos` VALUES (13,'ERP','Gestão empresarial com sistemas integrados (Protheus).',100,'2026-01-26 03:22:34',NULL,NULL,NULL,NULL,1),(15,'Zendesk','Plataforma de atendimento e suporte.',40,'2026-01-26 03:22:35',NULL,NULL,NULL,NULL,1),(20,'Programação Web','Aprenda HTML, CSS e JavaScript.',120,'2026-01-26 10:38:12',NULL,NULL,NULL,NULL,1),(21,'Cybersegurança','Fundamentos de segurança da informação e proteção.',80,'2026-01-26 10:38:12',NULL,NULL,NULL,NULL,1),(23,'Pacote Office','Word, Excel e PowerPoint para empresas.',60,'2026-01-26 10:38:12',NULL,NULL,NULL,'Neste curso você irá aprender sobre o Pacote Office.',1),(24,'Rotinas Administrativas','Fluxos de escritório e documentação.',80,'2026-01-26 10:38:12',NULL,NULL,NULL,NULL,1),(25,'Backend','Desenvolvimento de APIs e lógica de servidor (PHP/SQL).',120,'2026-01-26 11:03:48','uploads/thumbnails/1769425521_34cd9c1becfc.png',NULL,NULL,'Neste curso, você irá aprender os fundamentos do desenvolvimento backend, entendendo como funcionam os sistemas por trás das aplicações.\r\n\r\nVocê irá trabalhar com lógica de programação, bancos de dados e criação de APIs para permitir a comunicação entre o sistema e a interface do usuário. Também aprenderá conceitos básicos de segurança, organização de código e publicação de aplicações.\r\n\r\nAo final, você estará apto a desenvolver estruturas backend funcionais, seguras e bem organizadas.',1);
/*!40000 ALTER TABLE `cursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inscricoes`
--

DROP TABLE IF EXISTS `inscricoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inscricoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `curso_id` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pendente',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dados_formulario` text,
  `data_inicio` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inscricoes`
--

LOCK TABLES `inscricoes` WRITE;
/*!40000 ALTER TABLE `inscricoes` DISABLE KEYS */;
INSERT INTO `inscricoes` VALUES (1,4,3,'pendente','2026-01-21 00:55:42',NULL,NULL),(2,5,3,'pendente','2026-01-21 02:45:38',NULL,NULL),(3,5,4,'pendente','2026-01-21 02:56:53',NULL,NULL),(4,4,4,'pendente','2026-01-21 03:22:19',NULL,NULL),(5,6,4,'pendente','2026-01-21 03:43:48',NULL,NULL),(6,4,5,'pendente','2026-01-22 03:12:09',NULL,NULL),(7,4,7,'pendente','2026-01-22 03:38:31',NULL,NULL),(8,5,7,'pendente','2026-01-22 03:42:14',NULL,NULL),(9,4,9,'pendente','2026-01-22 04:23:23',NULL,NULL),(10,5,9,'pendente','2026-01-22 04:24:05',NULL,NULL),(11,4,10,'pendente','2026-01-22 04:36:12',NULL,NULL),(12,5,10,'pendente','2026-01-22 04:39:15',NULL,NULL),(13,4,11,'pendente','2026-01-22 05:02:06',NULL,NULL),(14,5,11,'pendente','2026-01-22 05:05:28',NULL,NULL),(15,5,12,'reprovado','2026-01-26 03:24:34','{\"renda_per_capita\":\"Ate 1 salario\",\"pessoas_residencia\":\"3\",\"acesso_internet\":\"Sim, ambos\",\"escola_publica\":\"Publica\",\"trabalha\":\"Nao\",\"motivo\":\"faz total sentido para mim\",\"data_solicitacao\":\"2026-01-26 03:24:34\"}','2026-01-28'),(16,5,16,'aprovado','2026-01-26 09:20:45','{\"renda_per_capita\":\"Ate 1 salario\",\"pessoas_residencia\":\"2\",\"acesso_internet\":\"Sim, ambos\",\"escola_publica\":\"Publica\",\"trabalha\":\"Nao\",\"motivo\":\"\",\"data_solicitacao\":\"2026-01-26 09:20:45\"}','2026-01-26'),(17,5,25,'aprovado','2026-01-26 11:28:51','{\"renda_per_capita\":\"Ate 1 salario\",\"pessoas_residencia\":\"2\",\"acesso_internet\":\"Sim, ambos\",\"escola_publica\":\"Publica\",\"trabalha\":\"Nao\",\"motivo\":\"É um curso que irá  mudar a minha realidade!\",\"data_solicitacao\":\"2026-01-26 11:28:51\"}','2026-01-26'),(18,5,23,'aprovado','2026-01-26 11:30:49','{\"renda_per_capita\":\"Ate 1 salario\",\"pessoas_residencia\":\"4\",\"acesso_internet\":\"Sim, ambos\",\"escola_publica\":\"Publica\",\"trabalha\":\"Nao\",\"motivo\":\"\",\"data_solicitacao\":\"2026-01-26 11:30:49\"}','2026-04-01'),(19,7,20,'pendente','2026-01-26 11:36:54','{\"renda_per_capita\":\"Ate 1 salario\",\"pessoas_residencia\":\"8\",\"acesso_internet\":\"Apenas internet (celular)\",\"escola_publica\":\"Publica\",\"trabalha\":\"Nao\",\"motivo\":\"\",\"data_solicitacao\":\"2026-01-26 11:36:54\"}',NULL),(20,7,25,'aprovado','2026-01-26 11:40:45','{\"renda_per_capita\":\"Ate 1 salario\",\"pessoas_residencia\":\"9\",\"acesso_internet\":\"Apenas internet (celular)\",\"escola_publica\":\"Particular com bolsa\",\"trabalha\":\"Sim formal\",\"motivo\":\"\",\"data_solicitacao\":\"2026-01-26 11:40:45\"}','2026-01-26');
/*!40000 ALTER TABLE `inscricoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `integracoes_log`
--

DROP TABLE IF EXISTS `integracoes_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `integracoes_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sistema` varchar(50) DEFAULT NULL,
  `payload` text,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `integracoes_log`
--

LOCK TABLES `integracoes_log` WRITE;
/*!40000 ALTER TABLE `integracoes_log` DISABLE KEYS */;
INSERT INTO `integracoes_log` VALUES (1,'RD Station','{\"user_id\":\"5\",\"curso_id\":\"3\"}','2026-01-21 02:45:39'),(2,'RD Station','{\"user_id\":\"5\",\"curso_id\":\"4\"}','2026-01-21 02:56:53'),(3,'RD Station','{\"user_id\":\"4\",\"curso_id\":\"4\"}','2026-01-21 03:22:19'),(4,'RD Station','{\"user_id\":\"6\",\"curso_id\":\"4\"}','2026-01-21 03:43:48'),(5,'RD Station','{\"user_id\":\"4\",\"curso_id\":\"3\"}','2026-01-22 02:46:15'),(6,'RD Station','{\"user_id\":\"4\",\"curso_id\":\"5\"}','2026-01-22 03:12:09'),(7,'RD Station','{\"user_id\":\"4\",\"curso_id\":\"7\"}','2026-01-22 03:38:31'),(8,'RD Station','{\"user_id\":\"5\",\"curso_id\":\"7\"}','2026-01-22 03:42:14'),(9,'RD Station','{\"user_id\":\"4\",\"curso_id\":\"9\"}','2026-01-22 04:23:23'),(10,'RD Station','{\"user_id\":\"5\",\"curso_id\":\"9\"}','2026-01-22 04:24:05'),(11,'RD Station','{\"user_id\":\"4\",\"curso_id\":\"10\"}','2026-01-22 04:36:12'),(12,'RD Station','{\"user_id\":\"5\",\"curso_id\":\"10\"}','2026-01-22 04:39:15'),(13,'RD Station','{\"user_id\":\"4\",\"curso_id\":\"11\"}','2026-01-22 05:02:06'),(14,'RD Station','{\"user_id\":\"5\",\"curso_id\":\"11\"}','2026-01-22 05:05:28'),(15,'RD Station','{\"user_id\":\"5\",\"curso_id\":\"12\",\"status\":\"pendente\"}','2026-01-26 03:24:34'),(16,'RD Station','{\"user_id\":\"5\",\"curso_id\":\"16\",\"status\":\"pendente\"}','2026-01-26 09:20:45'),(17,'RD Station','{\"user_id\":\"5\",\"curso_id\":\"25\",\"status\":\"pendente\"}','2026-01-26 11:28:51'),(18,'RD Station','{\"user_id\":\"5\",\"curso_id\":\"23\",\"status\":\"pendente\"}','2026-01-26 11:30:49'),(19,'RD Station','{\"user_id\":\"7\",\"curso_id\":\"20\",\"status\":\"pendente\"}','2026-01-26 11:36:54'),(20,'RD Station','{\"user_id\":\"7\",\"curso_id\":\"25\",\"status\":\"pendente\"}','2026-01-26 11:40:45');
/*!40000 ALTER TABLE `integracoes_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `progresso`
--

DROP TABLE IF EXISTS `progresso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `progresso` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `aula_id` int DEFAULT NULL,
  `concluida` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `progresso`
--

LOCK TABLES `progresso` WRITE;
/*!40000 ALTER TABLE `progresso` DISABLE KEYS */;
INSERT INTO `progresso` VALUES (1,5,1,1),(2,6,2,1),(3,4,1,1),(4,4,3,1),(5,4,4,1),(6,5,7,1),(7,5,8,1),(8,5,9,1),(9,5,11,1);
/*!40000 ALTER TABLE `progresso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topicos`
--

DROP TABLE IF EXISTS `topicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `topicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `curso_id` int NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `ordem` int DEFAULT '1',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_topicos_curso` (`curso_id`),
  KEY `idx_topicos_curso_ordem` (`curso_id`,`ordem`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topicos`
--

LOCK TABLES `topicos` WRITE;
/*!40000 ALTER TABLE `topicos` DISABLE KEYS */;
INSERT INTO `topicos` VALUES (1,9,'Introdução',1,'2026-01-22 04:33:27');
/*!40000 ALTER TABLE `topicos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('aluno','admin') DEFAULT 'aluno',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (4,'Gabriel','gabriel@gmail.com','$2y$10$aq5txCQLTdSTo9ZYhmHPcOh/w/jgkKtEnYCI2ZGKGcbNj7vFwGlSW','admin','2026-01-21 00:37:15',NULL),(5,'alunogab','aluno@gmail.com','$2y$10$Rf7RHlJZCDYq3zvyX5Kise.mR8X40rvjMjXvXoncQwdWNgfCrDHI2','aluno','2026-01-21 01:07:03',NULL),(6,'teste nome um','nomeum@gmail.com','$2y$10$4RrtHXD9LfVp1SIStUUxwOGTJVxFHQsogS.tnFmrKFZVmE25g39TW','aluno','2026-01-21 03:43:35',NULL),(7,'Ernesto Haberkorn','ernesto@gmail.com','$2y$10$K5dTGUpRzaXd3jgBqjAVA./GEbcaWZ6NhHi293zbAGq14dyGTxEo.','aluno','2026-01-26 11:36:20',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'ios'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-26 10:36:05
