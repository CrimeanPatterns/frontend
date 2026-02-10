function progressBarSetPosition(id, position){
    var pb = document.getElementById(id);
    var sp = pb.getElementsByTagName("span");
    var p = pb.getElementsByTagName("p");
    sp[0].style.width = position + "%";
    p[0].innerHTML = position + "%";
}