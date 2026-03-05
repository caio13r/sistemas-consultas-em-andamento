# Ordenação Independente por Label Pai - Sub-Labels

## 🎯 **Funcionalidade Implementada**

A página de gerenciamento de labels agora suporta **ordenação independente por label pai** para as sub-labels. Isso significa que cada grupo de sub-labels tem sua própria sequência de ordenação começando do 1.

## 🔄 **Como Funciona**

### **Antes (Ordenação Global)**
```
Sub-Label A (Label Pai 1) → Ordem: 1
Sub-Label B (Label Pai 1) → Ordem: 2
Sub-Label C (Label Pai 2) → Ordem: 3
Sub-Label D (Label Pai 2) → Ordem: 4
```

### **Agora (Ordenação Independente)**
```
📋 Label Pai 1:
  - Sub-Label A → Ordem: 1
  - Sub-Label B → Ordem: 2

📋 Label Pai 2:
  - Sub-Label C → Ordem: 1
  - Sub-Label D → Ordem: 2
```

## 🛠️ **Implementação Técnica**

### **1. Frontend (JavaScript)**

#### **Agrupamento Visual**
- Sub-labels são agrupadas visualmente por label pai
- Cabeçalhos de grupo mostram o nome da label pai e quantidade de sub-labels
- Cada linha tem atributo `data-parent-id` para identificação

#### **Ordenação Inteligente**
```javascript
function updateChildOrder() {
    const groupedOrders = {};
    
    // Agrupar por label pai
    $('#childLabelsTableBody tr.sortable-item').each(function(index) {
        const id = $(this).data('id');
        const parentId = $(this).attr('data-parent-id');
        
        if (id && parentId) {
            if (!groupedOrders[parentId]) {
                groupedOrders[parentId] = [];
            }
            
            // Posição relativa dentro do grupo
            groupedOrders[parentId].push({ 
                id: id, 
                relativePosition: groupedOrders[parentId].length + 1 
            });
        }
    });
    
    // Enviar para backend
    $.post(API_BASE_URL, {
        action: 'update_child_order_grouped',
        grouped_orders: JSON.stringify(groupedOrders)
    });
}
```

### **2. Backend (PHP)**

#### **Nova Ação no Controller**
```php
case 'update_child_order_grouped':
    $groupedOrders = json_decode($_POST['grouped_orders'] ?? '{}', true);
    $result = $labels->updateChildLabelsOrderGrouped($groupedOrders);
    break;
```

#### **Método de Ordenação Agrupada**
```php
public function updateChildLabelsOrderGrouped($groupedOrders) {
    $this->db->beginTransaction();
    
    foreach ($groupedOrders as $parentId => $orders) {
        foreach ($orders as $order) {
            $sql = "UPDATE tbl_child_labels 
                    SET display_order = :order 
                    WHERE id_label = :id AND fk_label = :parent_id";
            $stmt->execute([
                'order' => $order['relativePosition'],
                'id' => $order['id'],
                'parent_id' => $parentId
            ]);
        }
    }
    
    $this->db->commit();
    $this->cacheLabelsRedis();
    return true;
}
```

## 🎨 **Interface Visual**

### **Agrupamento Visual**
- **Cabeçalhos de Grupo**: Mostram o nome da label pai e contagem de sub-labels
- **Separação Visual**: Bordas coloridas e espaçamento entre grupos
- **Ícones Indicativos**: 📋 para mostrar que é um grupo independente

### **Estilos CSS**
```css
.table-group-header {
    background-color: #f8f9fa !important;
    border-left: 4px solid #007bff;
}

.sortable-item[data-parent-id]:hover {
    border-left-color: #007bff;
    background-color: #f8f9fa;
}
```

## 📊 **Estrutura de Dados**

### **Dados Enviados para Backend**
```json
{
    "1": [  // Label Pai ID 1
        {"id": 10, "relativePosition": 1},
        {"id": 11, "relativePosition": 2},
        {"id": 12, "relativePosition": 3}
    ],
    "2": [  // Label Pai ID 2
        {"id": 20, "relativePosition": 1},
        {"id": 21, "relativePosition": 2}
    ]
}
```

### **Resultado no Banco**
```sql
-- Sub-labels da Label Pai 1
UPDATE tbl_child_labels SET display_order = 1 WHERE id_label = 10 AND fk_label = 1;
UPDATE tbl_child_labels SET display_order = 2 WHERE id_label = 11 AND fk_label = 1;
UPDATE tbl_child_labels SET display_order = 3 WHERE id_label = 12 AND fk_label = 1;

-- Sub-labels da Label Pai 2
UPDATE tbl_child_labels SET display_order = 1 WHERE id_label = 20 AND fk_label = 2;
UPDATE tbl_child_labels SET display_order = 2 WHERE id_label = 21 AND fk_label = 2;
```

## 🚀 **Benefícios**

### **1. Organização Lógica**
- Cada label pai mantém sua própria sequência
- Facilita a organização hierárquica
- Evita conflitos de ordenação entre grupos

### **2. Flexibilidade**
- Permite reorganizar sub-labels sem afetar outros grupos
- Mantém a estrutura organizacional do sistema
- Facilita manutenção e gestão

### **3. Experiência do Usuário**
- Visualização clara dos grupos
- Feedback visual durante reordenação
- Interface intuitiva e responsiva

## 🔧 **Como Usar**

### **1. Acessar a Página**
- Vá para `/gerenciar-labels`
- Clique na aba **"Sub-Labels"**

### **2. Visualizar Grupos**
- Sub-labels são automaticamente agrupadas por label pai
- Cabeçalhos mostram informações do grupo
- Cada grupo tem ordenação independente

### **3. Reordenar**
- Clique em **"Reordenar"**
- Arraste sub-labels dentro do mesmo grupo
- Cada grupo mantém sequência 1, 2, 3...
- Clique em **"Finalizar"** para salvar

### **4. Verificar Resultado**
- Ordem é aplicada imediatamente
- Cache Redis é atualizado automaticamente
- Mudanças refletem em todo o sistema

## 🧪 **Testes Recomendados**

### **Teste 1: Ordenação Básica**
1. Reordene sub-labels de um grupo
2. Verifique se a ordem começa do 1
3. Confirme que outros grupos não são afetados

### **Teste 2: Múltiplos Grupos**
1. Reordene sub-labels em diferentes grupos
2. Verifique se cada grupo mantém sequência independente
3. Confirme que não há conflitos

### **Teste 3: Integração**
1. Reordene sub-labels
2. Visite uma página que usa sub-labels
3. Verifique se a nova ordem é aplicada

## 📝 **Arquivos Modificados**

1. **`src/views/gerenciarLabels.php`**
   - Função `updateChildOrder()` modificada
   - Função `renderChildLabelsTable()` com agrupamento
   - Estilos CSS para grupos
   - Interface explicativa

2. **`src/services/labels-admin/labels-controller.php`**
   - Nova ação `update_child_order_grouped`

3. **`src/lib/Labels.php`**
   - Novo método `updateChildLabelsOrderGrouped()`

## ✅ **Resultado Final**

✅ **Ordenação independente por label pai**  
✅ **Interface visual agrupada**  
✅ **Feedback visual durante reordenação**  
✅ **Cache automático atualizado**  
✅ **Compatibilidade com sistema existente**  

A funcionalidade está totalmente implementada e pronta para uso, proporcionando uma experiência de organização muito mais intuitiva e lógica para os administradores do sistema. 