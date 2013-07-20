var wcMoIPSuccess = function(data){
    alert('Sucesso\n' + JSON.stringify(data));
    window.open(data.url);
};

var wcMoIPFail = function(data) {
    alert('Falha\n' + JSON.stringify(data));
};
