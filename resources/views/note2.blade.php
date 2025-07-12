<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enhanced Drag and Drop</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .ghost {
      opacity: 0.5;
      transform: scale(0.9);
    }
  </style>
</head>
<body class="bg-gray-50 p-5">
  <div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-center text-blue-600 mb-6">Enhanced Drag and Drop</h1>
    
    <!-- Node Panel -->
    <div class="flex flex-wrap gap-4 mb-8 p-4 bg-white rounded-lg shadow-sm">
      <div 
        class="node px-6 py-3 bg-white rounded-lg shadow-md cursor-grab select-none border-2 border-transparent hover:border-blue-500 hover:shadow-lg transition-all duration-200"
        draggable="true"
        data-type="input"
      >
        INPUT NODE
      </div>
      <div 
        class="node px-6 py-3 bg-white rounded-lg shadow-md cursor-grab select-none border-2 border-transparent hover:border-blue-500 hover:shadow-lg transition-all duration-200"
        draggable="true"
        data-type="default"
      >
        DEFAULT NODE
      </div>
      <div 
        class="node px-6 py-3 bg-white rounded-lg shadow-md cursor-grab select-none border-2 border-transparent hover:border-blue-500 hover:shadow-lg transition-all duration-200"
        draggable="true"
        data-type="output"
      >
        OUTPUT NODE
      </div>
    </div>
    
    <!-- Workspace -->
    <div 
      id="workspace"
      class="min-h-[500px] bg-white rounded-xl shadow-sm p-6 border-2 border-dashed border-gray-300 relative overflow-hidden"
    >
      <p class="text-gray-500 text-center mt-[200px]">Drag nodes here to add them to your workspace</p>
    </div>
    
    <!-- Controls -->
    <div class="flex gap-3 mt-6">
      <button 
        id="saveLayout"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
      >
        Save Layout
      </button>
      <button 
        id="clearWorkspace"
        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
      >
        Clear Workspace
      </button>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const workspace = document.getElementById('workspace');
      const saveBtn = document.getElementById('saveLayout');
      const clearBtn = document.getElementById('clearWorkspace');
      let draggedNode = null;
      let nodeCounter = 0;
      let ghostElement = null;
      
      // Make panel nodes draggable
      document.querySelectorAll('.node').forEach(node => {
        node.addEventListener('dragstart', dragStart);
        node.addEventListener('dragend', dragEnd);
      });
      
      // Workspace drag events
      workspace.addEventListener('dragover', dragOver);
      workspace.addEventListener('dragenter', dragEnter);
      workspace.addEventListener('dragleave', dragLeave);
      workspace.addEventListener('drop', drop);
      
      // Button events
      saveBtn.addEventListener('click', saveLayout);
      clearBtn.addEventListener('click', clearWorkspace);
      
      function dragStart(e) {
        draggedNode = e.target;
        e.dataTransfer.setData('text/plain', draggedNode.dataset.type);
        e.dataTransfer.effectAllowed = 'copy';
        
        // Create ghost element
        ghostElement = draggedNode.cloneNode(true);
        ghostElement.classList.add('ghost', 'absolute', 'pointer-events-none');
        ghostElement.style.width = `${draggedNode.offsetWidth}px`;
        document.body.appendChild(ghostElement);
        
        // Update ghost position during drag
        document.addEventListener('dragover', updateGhostPosition);
      }
      
      function updateGhostPosition(e) {
        if (!ghostElement) return;
        ghostElement.style.left = `${e.clientX - ghostElement.offsetWidth/2}px`;
        ghostElement.style.top = `${e.clientY - ghostElement.offsetHeight/2}px`;
      }
      
      function dragEnd() {
        if (ghostElement) {
          ghostElement.remove();
          ghostElement = null;
        }
        document.removeEventListener('dragover', updateGhostPosition);
        draggedNode.classList.remove('opacity-50', 'cursor-grabbing');
        draggedNode = null;
      }
      
      function dragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
      }
      
      function dragEnter(e) {
        e.preventDefault();
        workspace.classList.add('border-blue-500', 'bg-blue-50');
      }
      
      function dragLeave() {
        workspace.classList.remove('border-blue-500', 'bg-blue-50');
      }
      
      function drop(e) {
        e.preventDefault();
        workspace.classList.remove('border-blue-500', 'bg-blue-50');
        
        if (!draggedNode) return;
        
        // Create new node for workspace
        const newNode = draggedNode.cloneNode(true);
        newNode.id = `node-${nodeCounter++}`;
        newNode.classList.add('absolute', 'cursor-move', 'dropped-node');
        newNode.draggable = false;
        
        // Position the new node at drop location
        const rect = workspace.getBoundingClientRect();
        const x = e.clientX - rect.left - (ghostElement?.offsetWidth/2 || 0);
        const y = e.clientY - rect.top - (ghostElement?.offsetHeight/2 || 0);
        
        newNode.style.left = `${x}px`;
        newNode.style.top = `${y}px`;
        
        // Make draggable within workspace
        makeDraggable(newNode);
        
        workspace.appendChild(newNode);
        workspace.querySelector('p')?.remove();
        
        // Clean up ghost
        if (ghostElement) {
          ghostElement.remove();
          ghostElement = null;
        }
      }
      
      function makeDraggable(element) {
        let isDragging = false;
        let offsetX, offsetY;
        
        element.addEventListener('mousedown', startDrag);
        
        function startDrag(e) {
          if (e.button !== 0) return; // Only left mouse button
          
          isDragging = true;
          const rect = element.getBoundingClientRect();
          offsetX = e.clientX - rect.left;
          offsetY = e.clientY - rect.top;
          
          element.style.zIndex = '100';
          element.classList.add('shadow-lg', 'border-blue-500');
          
          document.addEventListener('mousemove', drag);
          document.addEventListener('mouseup', stopDrag);
        }
        
        function drag(e) {
          if (!isDragging) return;
          
          const workspaceRect = workspace.getBoundingClientRect();
          let x = e.clientX - workspaceRect.left - offsetX;
          let y = e.clientY - workspaceRect.top - offsetY;
          
          // Constrain to workspace boundaries
          x = Math.max(0, Math.min(x, workspaceRect.width - element.offsetWidth));
          y = Math.max(0, Math.min(y, workspaceRect.height - element.offsetHeight));
          
          element.style.left = `${x}px`;
          element.style.top = `${y}px`;
        }
        
        function stopDrag() {
          isDragging = false;
          element.style.zIndex = '';
          element.classList.remove('shadow-lg', 'border-blue-500');
          
          document.removeEventListener('mousemove', drag);
          document.removeEventListener('mouseup', stopDrag);
        }
      }
      
      function saveLayout() {
        const nodes = workspace.querySelectorAll('.dropped-node');
        const layout = [];
        
        nodes.forEach(node => {
          layout.push({
            type: node.dataset.type,
            text: node.textContent,
            x: parseInt(node.style.left),
            y: parseInt(node.style.top),
            id: node.id
          });
        });
        
        console.log('Layout to save:', layout);
        alert('Layout saved to console (check developer tools)');
      }
      
      function clearWorkspace() {
        if (confirm('Are you sure you want to clear the workspace?')) {
          workspace.innerHTML = '<p class="text-gray-500 text-center mt-[200px]">Drag nodes here to add them to your workspace</p>';
        }
      }
    });
  </script>
</body>
</html>