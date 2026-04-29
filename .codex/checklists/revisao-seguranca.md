# Checklist de segurança e LGPD

## Auth

- [ ] Senha com hash forte.
- [ ] JWT com expiração.
- [ ] Usuário inativo bloqueado.
- [ ] Falhas de login auditadas.
- [ ] Rotas sensíveis protegidas.

## ACL

- [ ] Permissão por perfil.
- [ ] Permissão por usuário/override quando aplicável.
- [ ] Profissional não acessa contrato financeiro.
- [ ] Financeiro não acessa conteúdo clínico sem permissão.
- [ ] Profissional só edita registros próprios, salvo permissão superior.

## Dados sensíveis

- [ ] Dados clínicos não aparecem em logs desnecessários.
- [ ] Downloads sensíveis auditados.
- [ ] Prontuário acessado com permissão.
- [ ] Máscara de dados quando aplicável.
- [ ] Soft delete em entidades relevantes.

## Uploads

- [ ] Extensão validada.
- [ ] MIME validado.
- [ ] Tamanho validado.
- [ ] Checksum calculado.
- [ ] Download protegido.
- [ ] Exclusão lógica.

## Auditoria

- [ ] Login/logout.
- [ ] Falhas de login.
- [ ] Alterações cadastrais sensíveis.
- [ ] Acesso a prontuário.
- [ ] Alterações financeiras.
- [ ] Alterações de agenda.
- [ ] Exportação TISS/XML.
- [ ] Upload/download sensível.
