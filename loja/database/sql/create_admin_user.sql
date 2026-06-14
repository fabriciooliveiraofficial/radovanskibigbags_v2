-- Cria (ou atualiza, se já existir) o usuário do painel /admin em produção.
-- Cole este script no editor SQL do phpMyAdmin (hPanel da Hostinger) e execute.
--
-- Login: fabriciooliveiraofficial@gmail.com
-- Senha: Fdm399788896528168172@#$  (já vai criptografada como hash bcrypt abaixo)

INSERT INTO users (name, email, email_verified_at, password, created_at, updated_at)
VALUES (
    'Fabricio Oliveira',
    'fabriciooliveiraofficial@gmail.com',
    NOW(),
    '$2y$12$5lRD5rtIlOtSaRMsiRcrEOutsP0t5paYwIPQEwo7ItH.O3pJVbMyy',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    password = '$2y$12$5lRD5rtIlOtSaRMsiRcrEOutsP0t5paYwIPQEwo7ItH.O3pJVbMyy',
    updated_at = NOW();
