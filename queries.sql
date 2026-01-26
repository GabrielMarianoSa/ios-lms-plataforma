-- queries.sql — consultas úteis para checagem e exploração

-- 1) Mostrar tabelas
SHOW TABLES;

-- 2) Estrutura de `curso_infos` (se existir)
DESCRIBE curso_infos;

-- 3) Cursos + número de inscritos
SELECT c.id, c.titulo, COUNT(i.id) AS total_inscritos
FROM cursos c
LEFT JOIN inscricoes i ON i.curso_id = c.id
GROUP BY c.id, c.titulo
ORDER BY total_inscritos DESC;

-- 4) Dados de `curso_infos` (datas/local/modalidade/turno/vagas)
SELECT * FROM curso_infos ORDER BY curso_id DESC LIMIT 200;

-- 5) Últimos 20 logs de integrações
SELECT id, sistema, criado_em, payload
FROM integracoes_log
ORDER BY id DESC
LIMIT 20;

-- 6) Progresso por usuário em um curso (troque :curso_id)
SELECT p.user_id, u.nome, COUNT(p.aula_id) AS aulas_concluidas
FROM progresso p
JOIN users u ON u.id = p.user_id
JOIN aulas a ON a.id = p.aula_id
WHERE a.curso_id = /*curso_id*/ 1
AND p.concluida = 1
GROUP BY p.user_id, u.nome
ORDER BY aulas_concluidas DESC;
