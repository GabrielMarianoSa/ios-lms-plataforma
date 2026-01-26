-- Tabela opcional para enriquecer dados do curso (sem alterar tabelas existentes)
-- Banco: ios
-- Importante: isto NÃO executa nada automaticamente.

CREATE TABLE IF NOT EXISTS curso_infos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  curso_id INT NOT NULL,
  modalidade VARCHAR(50) NULL,
  local VARCHAR(120) NULL,
  data_inicio DATE NULL,
  data_fim DATE NULL,
  turno VARCHAR(30) NULL,
  vagas INT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_curso_infos_curso_id (curso_id),
  KEY idx_curso_infos_datas (data_inicio, data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Opcional (recomendado se suas tabelas já estiverem em InnoDB e você quiser integridade):
-- ALTER TABLE curso_infos
--   ADD CONSTRAINT fk_curso_infos_curso
--   FOREIGN KEY (curso_id) REFERENCES cursos(id)
--   ON DELETE CASCADE;
