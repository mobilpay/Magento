document.addEventListener("DOMContentLoaded", () => {
    const configurebButton = document.getElementById("payment_net_card-head");
    if(configurebButton){
        if (configurebButton.disabled){
            document.getElementById("payment_net_card-head").className = "button action-configure";
            document.getElementById("payment_net_card-head").removeAttribute("disabled");
        } else {
            //
        }    
    }else{
        //
    }    
  });