container=document.getElementsByClassName("container").item(0);
index=0;
t=3000;
function auto(){
    caroul(index+1);
    setTimeout("auto()",t);
}
function caroul(i){
    index=(i+2)%2;
    a=-index*100;
    container.style.transform="translateX("+a+"%)";
    t=3000;
}