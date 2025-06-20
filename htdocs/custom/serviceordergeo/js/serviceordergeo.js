document.addEventListener('DOMContentLoaded', function() {
    var inputAddr = document.getElementById('destination_address');
    if (!inputAddr) return;
    inputAddr.addEventListener('blur', function() {
        var address = inputAddr.value;
        if (!address) return;
        var url = DOL_URL_ROOT + '/custom/serviceordergeo/htdocs/api/serviceordergeo/geocode.php?address=' + encodeURIComponent(address);
        fetch(url)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.latitude && data.longitude) {
                    var latField = document.getElementById('latitude');
                    if (!latField) {
                        latField = document.createElement('input');
                        latField.type = 'hidden'; latField.name = 'latitude'; latField.id = 'latitude';
                        inputAddr.parentNode.appendChild(latField);
                    }
                    var lonField = document.getElementById('longitude');
                    if (!lonField) {
                        lonField = document.createElement('input');
                        lonField.type = 'hidden'; lonField.name = 'longitude'; lonField.id = 'longitude';
                        inputAddr.parentNode.appendChild(lonField);
                    }
                    latField.value = data.latitude;
                    lonField.value = data.longitude;
                }
            })
            .catch(function() {
                alert('Falha ao obter geolocalização');
            });
    });
}); 