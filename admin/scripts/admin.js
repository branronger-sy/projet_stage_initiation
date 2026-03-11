document.addEventListener('DOMContentLoaded', function(){
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    if(toggle && sidebar){
      toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
      });
    }
    document.addEventListener('click', (e)=>{
      const sidebarOpen = sidebar && sidebar.classList.contains('open');
      if(sidebarOpen && !e.target.closest('.sidebar') && !e.target.closest('.sidebar-toggle')){
        sidebar.classList.remove('open');
      }
    });
  });
  