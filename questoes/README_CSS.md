# Guia de Arquivos CSS

## Estrutura Atual

### Arquivos CSS Principais:
1. **style.css** - CSS principal do sistema (USE ESTE)
2. **modern-style.css** - Estilos modernos complementares

### Arquivos CSS Legados (NÃO USAR):
- **alternative-clean.css** - Legado, manter para compatibilidade
- **alternative-feedback.css** - Legado, manter para compatibilidade  
- **alternative-fix.css** - Legado, manter para compatibilidade

## Recomendação:

**USE APENAS:** `style.css` + `modern-style.css`

Os arquivos `alternative-*.css` são mantidos apenas para não quebrar código antigo que possa referenciá-los, mas não devem ser modificados ou usados em novos desenvolvimentos.

## Ordem de Importação Recomendada:

```html
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="modern-style.css">
```

## Futuro:

Quando possível, consolidar tudo em um único arquivo CSS minificado para produção.

