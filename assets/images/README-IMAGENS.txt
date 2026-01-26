Coloque aqui suas imagens reais substituindo os arquivos placeholder.

Onde cada imagem é usada / como alterar:
- `alunoestudando.jpg`: imagem do jovem na home. Caminho usado em [index.php](index.php#L18). Substitua este arquivo direto ou altere o src em [index.php](index.php#L18).
- `cadeirante.jpg`: imagem na seção "Nossa estrutura" da home. Caminho usado em [index.php](index.php#L36).
- `curso-banner.jpg`: placeholder para banners de curso. Atualmente não é referenciado automaticamente; para usar no curso, edite [curso.php](curso.php) e insira um `<img src="assets/images/curso-banner.jpg">` no topo do container.
- `avatar.png`: imagem padrão de perfil (quando o usuário não carregou foto). Local: `assets/images/avatar.png`.
- `logo.png`: logotipo do site (cabeçalho). Substitua por PNG ou SVG da sua marca.
- `totvs.png`, `dell.png`, `microsoft.png`, `zendesk.png`, `ibm.png`: placeholders para logos de parceiros. Se existir um arquivo local com o mesmo nome será usado; caso contrário, o site tenta carregar via CDN de ícones.

Tamanhos recomendados:
- Avatar (perfil): 256x256 px (variante 64x64 e 128x128 para miniaturas)
- Curso banner: 1200x400 px (proporção 3:1) ou 1200x600 px
- Hero / Aluno estudando: 1200x800 px
- Logos de parceiros: 200x80 px dentro de um quadro de 200x80 px

Boas práticas:
- Nomes sem espaços: use `-` ou `_`.
- Otimize imagens (TinyPNG, Squoosh, mozjpeg). JPG para fotos, PNG/SVG para logos.
- Para performance, considere WebP com fallback.

Se quiser que eu busque links oficiais das logos (TOTVS, Dell, Microsoft, Zendesk, IBM), diga "Buscar logos oficiais".
