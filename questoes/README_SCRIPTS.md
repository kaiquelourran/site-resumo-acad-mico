# Guia de Scripts Auxiliares

## Scripts de Desenvolvimento (Uso Administrativo Apenas)

### 🔧 Scripts de Configuração de Ambiente:

#### **force_local.php**
- **Propósito:** Força o sistema a usar configurações locais (XAMPP)
- **Uso:** Acessar uma vez para forçar ambiente local
- **ATENÇÃO:** Não usar em produção!

#### **force_online.php**
- **Propósito:** Força o sistema a usar configurações online (Hostinger)
- **Uso:** Acessar uma vez para forçar ambiente de produção
- **ATENÇÃO:** Usar apenas na Hostinger!

### 📊 Scripts de Manutenção de Dados:

#### **gerar_sql_limpo.php**
- **Propósito:** Gera arquivo SQL limpo para migração Hostinger
- **Uso:** Executar manualmente antes de fazer deploy
- **Resultado:** Cria arquivo SQL sem comandos específicos do phpMyAdmin

#### **remover_emojis.php**
- **Propósito:** Remove emojis de arquivos específicos
- **Uso:** Ferramenta de limpeza de dados
- **ATENÇÃO:** Fazer backup antes de usar!

### 🧪 Scripts de Teste:

#### **demo_comentarios.html**
- **Propósito:** Demonstração/teste do sistema de comentários
- **Uso:** Visualizar funcionalidades de comentários
- **Status:** Arquivo de documentação/demo

#### **inserir_alternativas_exemplo.php**
- **Propósito:** Script de exemplo para inserção de dados
- **Uso:** Referência para desenvolvedores
- **Status:** Exemplo educacional

### 📝 Scripts de Criação de Tabelas:

#### **criar_tabela_usuarios.php**
- **Propósito:** Cria tabela de usuários
- **Uso:** Executar UMA VEZ na instalação inicial

#### **criar_tabela_comentarios.php**
- **Propósito:** Cria tabela de comentários
- **Uso:** Executar UMA VEZ na instalação inicial

#### **criar_tabela_respostas_usuario.php**
- **Propósito:** Cria tabela de respostas
- **Uso:** Executar UMA VEZ na instalação inicial

#### **corrigir_tabela_usuarios.php**
- **Propósito:** Corrige/atualiza estrutura da tabela usuarios
- **Uso:** Executar se houver problemas com a tabela

## ⚠️ ATENÇÃO DE SEGURANÇA

**Todos esses scripts devem ser protegidos em produção!**

### Opções de proteção:

1. **Mover para pasta protegida** (Recomendado)
   ```
   mkdir -p dev/scripts
   mv force_*.php dev/scripts/
   mv criar_*.php dev/scripts/
   mv corrigir_*.php dev/scripts/
   ```

2. **Proteger com .htaccess**
   ```apache
   <Files "force_*.php">
       Order Deny,Allow
       Deny from all
   </Files>
   ```

3. **Remover em produção** (Mais seguro)
   - Deletar todos os scripts após configuração inicial

## 📚 Documentação Adicional

Para mais informações sobre cada script, consulte os comentários internos dos arquivos.

