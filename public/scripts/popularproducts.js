function scrollCarousel(direction) {
    const container = document.getElementById("prod-cont");
    const card = container.querySelector('.product-card');

    const cardWidth = card.offsetWidth + 20;
    container.scrollBy({
      left: direction * cardWidth,
      behavior: 'smooth'
    });
  }
  a=0;
  function wish(elm){
    if(a==0)
    {
        elm.style.backgroundColor="#f27171";
        a=1;
    }
    else
    {
        elm.style.backgroundColor="rgba(255, 255, 255, 0.8)";
        a=0;
    }
  }