-- Tabela para conteúdo editável do site
CREATE TABLE IF NOT EXISTS `site_conteudo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL COMMENT 'Identificador único (ex: home_titulo, home_subtitulo)',
  `valor` text NOT NULL COMMENT 'Conteúdo editável',
  `tipo` enum('texto','textarea','imagem','numero') DEFAULT 'texto' COMMENT 'Tipo de campo',
  `grupo` varchar(50) DEFAULT 'geral' COMMENT 'Agrupamento (home, sobre, contato)',
  `descricao` varchar(255) DEFAULT NULL COMMENT 'Descrição amigável para o admin',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave_unique` (`chave`),
  KEY `grupo_idx` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir conteúdo padrão da home
INSERT INTO `site_conteudo` (`chave`, `valor`, `tipo`, `grupo`, `descricao`) VALUES
('home_titulo', 'Oportunidade real para quem quer vencer.', 'texto', 'home', 'Título principal da home'),
('home_subtitulo', 'Cursos gratuitos de tecnologia e gestão para jovens e pessoas com deficiência.', 'textarea', 'home', 'Subtítulo da home'),
('home_badge', '24 anos transformando vidas', 'texto', 'home', 'Badge/etiqueta da home'),
('home_cta_texto', 'Inscreva-se agora', 'texto', 'home', 'Texto do botão principal'),
('home_banner', 'assets/images/banner-cursos.jpg', 'imagem', 'home', 'Imagem de destaque da home'),

('stats_anos', '24', 'numero', 'estatisticas', 'Anos de história'),
('stats_alunos_formados', '50000', 'numero', 'estatisticas', 'Total de alunos formados'),
('stats_alunos_ano', '1000', 'numero', 'estatisticas', 'Alunos por ano'),
('stats_empregabilidade', '83', 'numero', 'estatisticas', 'Percentual de empregabilidade'),

('sobre_titulo', 'Sobre o IOS', 'texto', 'sobre', 'Título da seção sobre'),
('sobre_texto', 'O Instituto da Oportunidade Social (IOS) transforma vidas há mais de 24 anos através de educação profissionalizante gratuita e de qualidade.', 'textarea', 'sobre', 'Texto sobre o IOS'),

('site_nome', 'Instituto da Oportunidade Social', 'texto', 'geral', 'Nome do site/instituto'),
('site_url', 'https://ios.org.br/', 'texto', 'geral', 'URL oficial do IOS')
ON DUPLICATE KEY UPDATE
  `valor` = VALUES(`valor`),
  `tipo` = VALUES(`tipo`),
  `grupo` = VALUES(`grupo`),
  `descricao` = VALUES(`descricao`);

-- Tabela para parceiros
CREATE TABLE IF NOT EXISTS `site_parceiros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `logo` varchar(255) NOT NULL COMMENT 'Caminho da imagem do logo',
  `ordem` int(11) DEFAULT 0 COMMENT 'Ordem de exibição',
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir parceiros padrão
INSERT INTO `site_parceiros` (`nome`, `logo`, `ordem`) VALUES
('TOTVS', 'assets/images/totvs.png', 1),
('Dell', 'assets/images/dell.png', 2),
('Microsoft', 'assets/images/microsoft.png', 3),
('Zendesk', 'assets/images/zendesk.png', 4),
('IBM', 'assets/images/ibm.png', 5)
ON DUPLICATE KEY UPDATE
  `logo` = VALUES(`logo`),
  `ordem` = VALUES(`ordem`);
