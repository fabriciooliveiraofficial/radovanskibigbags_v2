# Guia de implantação — Radovanski Big Bags (Hostinger Cloud Professional)

Loja virtual em **Laravel 13 + Filament 5 + MySQL**. Este guia leva do zero ao ar em `https://radovanskibigbags.com.br`.

---

## 1. Pré-requisitos na Hostinger

- Plano **Cloud Professional** (PHP 8.3+ e MySQL inclusos).
- Acesso ao **hPanel** e ao **SSH** (Cloud tem SSH liberado).
- Domínio `radovanskibigbags.com.br` apontado para a hospedagem.

---

## 2. Criar o banco de dados MySQL

No hPanel → **Bancos de Dados → MySQL**:

1. Crie um banco (ex: `u000000000_radovanski`).
2. Crie um usuário e **anote a senha**.
3. Associe o usuário ao banco com **todos os privilégios**.

---

## 3. Enviar os arquivos

**Importante:** a raiz do site na Hostinger é `public_html`. O Laravel serve a partir da pasta `public/`. Há duas abordagens:

### Opção A — recomendada (subpasta + ajuste de raiz)
1. Suba todo o projeto para uma pasta fora da web, ex: `~/radovanski` (via SSH `git clone` ou SFTP).
2. No hPanel → **Avançado → Domínios → alterar Document Root** do domínio para `~/radovanski/public`.

### Opção B — sem alterar document root
1. Suba o conteúdo de `public/` para `public_html/`.
2. Suba o restante do projeto para `~/radovanski`.
3. Edite `public_html/index.php` corrigindo os dois caminhos `require` para `~/radovanski/...`.

> Use a Opção A sempre que o painel permitir alterar o document root (no Cloud, permite).

---

## 4. Instalar dependências (via SSH)

```bash
cd ~/radovanski

# Dependências PHP (sem dev, otimizado para produção)
composer install --no-dev --optimize-autoloader

# Dependências JS + build dos assets (gera public/build)
npm install
npm run build
```

> Se o servidor não tiver Node, rode `npm run build` na sua máquina e suba a pasta `public/build` junto.

---

## 5. Configurar o ambiente

```bash
cp .env.production.example .env
php artisan key:generate
nano .env   # preencher DB_*, MAIL_* e conferir APP_URL
```

---

## 6. Migrar o banco e criar o admin

```bash
php artisan migrate --force

# Cria o usuário do painel (login em /admin)
php artisan make:filament-user

# (Opcional) popular com produtos/categorias/FAQ de exemplo:
php artisan db:seed --class=DemoSeeder --force
```

---

## 7. Permissões e link de storage

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

---

## 8. Otimizar para produção

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

> Após qualquer alteração de código/.env, rode `php artisan optimize:clear` e em seguida os comandos acima de novo.

---

## 9. HTTPS

No hPanel → **Segurança → SSL**: emita o certificado gratuito (Let's Encrypt) para o domínio e ative **forçar HTTPS**. O `APP_URL` já usa `https://`.

---

## 10. Tarefas agendadas (cron)

No hPanel → **Avançado → Cron Jobs**, adicione (a cada minuto):

```
* * * * * cd ~/radovanski && php artisan schedule:run >> /dev/null 2>&1
```

E um **backup diário do banco** (madrugada):

```
0 3 * * * mysqldump -u USUARIO -pSENHA BANCO > ~/backups/radovanski-$(date +\%Y\%m\%d).sql
```

---

## 11. Configuração inicial no painel (cliente faz)

Acesse `https://radovanskibigbags.com.br/admin` e em **Configurações → Dados da loja e frete**:

- Dados da empresa (nome, CNPJ, **WhatsApp**, endereço, horário, logo).
- Entrega própria: CEP de origem, preço/km, valor mínimo, raio.
- **Tokens de frete** (gratuitos):
  - **Melhor Envio**: painel → Integrações → Tokens → gerar token de produção.
  - **SuperFrete**: painel → Configurações → Token de API.
  - **Frenet**: painel → Minha Conta → Token de integração.
  - **OpenRouteService**: cadastro gratuito em `openrouteservice.org` → API key.
- Em **Formas de pagamento**, ajuste as condições aceitas.

---

## 12. SEO local — após o go-live (essencial)

1. **Google Business Profile** (o que faz aparecer no mapa): crie/reivindique o perfil "Radovanski Big Bags", categoria "Fornecedor de embalagens", endereço de Curitiba, fotos e link do site. É o maior fator de SEO local.
2. **Google Search Console**: adicione a propriedade, verifique e envie `https://radovanskibigbags.com.br/sitemap.xml`.
3. Peça avaliações aos clientes no Google — peso forte no ranking local.
4. Preencha o **SEO por produto/categoria** no painel usando termos + "Curitiba".

---

## Solução de problemas

| Sintoma | Causa provável | Solução |
|---|---|---|
| Erro 500 em branco | permissões | `chmod -R 775 storage bootstrap/cache` |
| CSS/JS não carrega | build ausente | `npm run build` e confirmar `public/build` no servidor |
| Imagens não aparecem | sem symlink | `php artisan storage:link` |
| Mudou .env e nada muda | cache | `php artisan optimize:clear` |
| Frete sempre "sob consulta" | tokens vazios/CEP origem | preencher em Configurações da loja |
