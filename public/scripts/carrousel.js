index=0;

function caroul(i){
    index=(i+2)%2;
    a=-index*100;
    container.style.transform="translateX("+a+"%)";
}