# Sistema de Gerenciamento de Labels - Documentação Completa

## Visão Geral

Este sistema oferece uma interface administrativa completa para gerenciamento de labels (etiquetas) do sistema de consultas, incluindo **ordenação hierárquica** onde as labels principais controlam a ordem do menu lateral e as sub-labels são organizadas dentro de cada label principal através da relação por `fk_label`.

## Características Principais

- ✅ **CRUD Completo**: Criar, editar, visualizar e excluir labels e sub-labels
- ✅ **Ordenação Hierárquica**: Sistema de dois níveis de ordenação
  - Labels principais ordenadas por `display_order` (controla ordem do menu lateral)
  - Sub-labels ordenadas por `display_order` dentro de cada label pai (através de `fk_label`)
- ✅ **Interface Responsiva**: Design moderno com Bootstrap 5
- ✅ **Visualização Hierárquica**: Aba dedicada para visualizar a estrutura completa
- ✅ **Cache Redis**: Atualização automática do cache após modificações
- ✅ **Controle de Acesso**: Restrito a administradores (grupo = 0)
- ✅ **Drag & Drop**: Reordenação intuitiva com SortableJS

## Interface do Sistema

### 1. Labels Principais
**Aba "Labels Principais"** - Gerencia as labels que aparecem no menu lateral principal
- Lista todas as labels principais com informações completas
- Funcionalidade de reordenação por drag & drop
- CRUD completo com validação

### 2. Sub-Labels  
**Aba "Sub-Labels"** - Gerencia as sub-labels organizadas hierarquicamente
- Exibe sub-labels com informação da label pai
- Ordenação hierárquica respeitando a relação `fk_label`
- Badge visual mostrando a qual label pai pertence cada sub-label

### 3. Visualização Hierárquica
**Aba "Visualização Hierárquica"** - Apresenta a estrutura completa em formato de árvore
- Cards para cada label principal
- Lista de sub-labels dentro de cada card
- Informações de ordem, status e ações diretas
- Visão geral da estrutura organizacional

## Funcionalidades Detalhadas

### Labels Principais

#### Visualizar Labels
- Acesse a aba "Labels Principais"
- Veja a lista ordenada por `display_order`
- Use DataTables para busca e paginação

#### Adicionar Nova Label
1. Clique em "Nova Label"
2. Preencha os campos:
   - **Label Key**: Identificador único (obrigatório)
   - **Nome da Label**: Nome exibido (obrigatório)
   - **URL**: Link de destino (opcional)
   - **Classe do Ícone**: Classe CSS FontAwesome (opcional)
   - **Descrição**: Descrição detalhada (opcional)
   - **Desabilitada**: Marque para ocultar (opcional)
3. Clique em "Salvar"

#### Editar Label
1. Clique no ícone de edição (✏️) na linha da label
2. Modifique os campos desejados
3. Clique em "Salvar"

#### Excluir Label
1. Clique no ícone de exclusão (🗑️) na linha da label
2. Confirme a exclusão
   - **⚠️ Atenção**: Isso também excluirá todas as sub-labels relacionadas!

#### Reordenar Labels Principais
1. Clique em "Reordenar" na aba "Labels Principais"
2. Arraste as linhas para reordenar
3. A ordem será salva automaticamente
4. Clique novamente em "Reordenar" para desativar o modo

### Sub-Labels

#### Visualizar Sub-Labels
- Acesse a aba "Sub-Labels"
- Veja a lista hierárquica com informações da label pai
- Ordenadas primeiro por label pai, depois por `display_order`

#### Adicionar Nova Sub-Label
1. Clique em "Nova Sub-Label"
2. Preencha os campos:
   - **Nome**: Nome da sub-label (obrigatório)
   - **Referencial**: Número de referência (obrigatório)
   - **Grupo**: Grupo de classificação (opcional)
   - **Label Pai**: Selecione a label principal (obrigatório)
   - **Descrição**: Descrição detalhada (opcional)
   - **Desabilitada**: Marque para ocultar (opcional)
3. Clique em "Salvar"

#### Editar/Excluir Sub-Label
- Funciona da mesma forma que as labels principais
- Use os ícones de ação na tabela

#### Reordenar Sub-Labels Hierarquicamente
1. Na aba "Sub-Labels", clique em "Reordenar"
2. Arraste as sub-labels para reordenar
3. **Sistema hierárquico**: Sub-labels são agrupadas automaticamente por label pai
4. A ordenação respeita a hierarquia estabelecida pela coluna `fk_label`

### Visualização Hierárquica

#### Acessar Estrutura Hierárquica
1. Clique na aba "Visualização Hierárquica"
2. O sistema carrega automaticamente a estrutura completa
3. Visualize a organização em formato de cards

#### Funcionalidades da Visualização
- **Cards por Label Principal**: Cada label principal é um card expandido
- **Sub-Labels Organizadas**: Lista dentro de cada card mostrando as sub-labels
- **Informações Completas**: Status, ordem, descrições e ações
- **Ações Diretas**: Edite ou exclua diretamente da visualização hierárquica

### Atualizar Cache

- Clique em "Atualizar Cache" no cabeçalho para forçar a atualização do cache Redis
- Isso é feito automaticamente após cada modificação

## Estrutura do Banco de Dados

### Tabela `tbl_labels` (Labels Principais)
```sql
CREATE TABLE tbl_labels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    label_key VARCHAR(255) NOT NULL,
    label_value VARCHAR(255) NOT NULL,
    url VARCHAR(255),
    class VARCHAR(255),
    description TEXT,
    disable TINYINT(1) DEFAULT 0,
    display_order INT NULL,  -- Adicionada pelo script SQL
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabela `tbl_child_labels` (Sub-Labels)
```sql
CREATE TABLE tbl_child_labels (
    id_label INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    referencial INT NOT NULL,
    grupo VARCHAR(255),
    descricao TEXT,
    disable TINYINT(1) DEFAULT 0,
    fk_label INT NOT NULL,  -- Chave estrangeira para tbl_labels.id
    display_order INT NULL,  -- Adicionada pelo script SQL para ordenação
    FOREIGN KEY (fk_label) REFERENCES tbl_labels(id)
);
```

## Scripts SQL Incluídos

### `script_sql_add_display_order_column.sql`
Adiciona a coluna `display_order` nas tabelas `tbl_labels` e `tbl_child_labels` para permitir ordenação customizada hierárquica.

**⚠️ Executar antes de usar a funcionalidade de reordenação:**
```sql
-- Execute este script no banco de dados
source script_sql_add_display_order_column.sql
```

**O script realiza:**
- Adiciona `display_order` em `tbl_labels` (ordenação do menu principal)
- Adiciona `display_order` em `tbl_child_labels` (ordenação hierárquica das sub-labels)
- Inicializa valores baseados em `id` e `referencial` respectivamente
- Consultas de verificação para confirmar a implementação

## Sistema de Ordenação Hierárquico

### Como Funciona
1. **Nível 1**: Labels principais ordenadas por `display_order`
   - Controla a ordem do menu lateral principal
   - Visível na navegação principal do sistema

2. **Nível 2**: Sub-labels ordenadas por `display_order` dentro de cada label pai
   - Agrupadas pela relação `fk_label` → `tbl_labels.id`
   - Organizadas hierarquicamente dentro de cada label principal
   - Mantém a estrutura organizacional do sistema

### Consulta Hierárquica
```sql
-- Exemplo de consulta que respeita a hierarquia
SELECT 
    l.id, l.label_value, l.display_order,
    cl.id_label, cl.nome, cl.display_order as child_order
FROM tbl_labels l
LEFT JOIN tbl_child_labels cl ON l.id = cl.fk_label
ORDER BY 
    COALESCE(l.display_order, l.id) ASC,
    COALESCE(cl.display_order, cl.referencial) ASC;
```

## API Endpoints

O controlador `labels-controller.php` fornece os seguintes endpoints:

### Labels Principais
- `GET ?action=list_labels` - Listar todas as labels ordenadas
- `GET ?action=get_label&id=X` - Obter label específica
- `POST action=insert_label` - Criar nova label
- `POST action=update_label` - Atualizar label existente
- `POST action=delete_label` - Excluir label
- `POST action=update_order` - Atualizar ordem das labels principais

### Sub-Labels
- `GET ?action=list_child_labels` - Listar todas as sub-labels com info do pai
- `GET ?action=list_child_labels&parent_id=X` - Listar sub-labels de um pai específico
- `GET ?action=get_child_label&id=X` - Obter sub-label específica
- `POST action=insert_child_label` - Criar nova sub-label
- `POST action=update_child_label` - Atualizar sub-label existente
- `POST action=delete_child_label` - Excluir sub-label
- `POST action=update_child_order` - Atualizar ordem hierárquica das sub-labels

### Hierarquia
- `GET ?action=get_hierarchy` - Obter estrutura hierárquica completa

### Cache
- `POST action=update_cache` - Forçar atualização do cache Redis

## Segurança

- **Verificação de Administrador**: Apenas usuários com `Session::get('grupo') == 0`
- **Validação de Dados**: Campos obrigatórios validados no frontend e backend
- **Transações SQL**: Operações de ordenação usam transações para consistência
- **Logs de Erro**: Erros registrados no log do sistema

## Tecnologias Utilizadas

- **Backend**: PHP 7.4+, PDO, Redis
- **Frontend**: Bootstrap 5, jQuery, DataTables, SortableJS
- **Banco**: MySQL/MariaDB
- **Cache**: Redis para armazenamento das labels

## Troubleshooting

### Erro: "Coluna display_order não existe"
**Solução**: Execute o script `script_sql_add_display_order_column.sql`

### Cache não atualiza
**Solução**: Use o botão "Atualizar Cache" ou verifique a conexão Redis

### Reordenação não funciona
**Solução**: 
1. Verifique se SortableJS está carregado
2. Confirme que o script SQL foi executado
3. Verifique permissões de administrador

### Sub-labels não aparecem na hierarquia
**Solução**: Verifique se a relação `fk_label` está correta na tabela `tbl_child_labels`

## Próximos Passos

- [ ] Implementar ordenação drag & drop também na visualização hierárquica
- [ ] Adicionar busca e filtros na visualização hierárquica
- [ ] Implementar histórico de alterações
- [ ] Adicionar importação/exportação de estrutura

---

**📝 Nota**: Este sistema implementa um controle hierárquico completo onde as labels principais definem a estrutura do menu e as sub-labels são organizadas dentro de cada label através da relação `fk_label`, proporcionando uma organização lógica e flexível do sistema de navegação. 