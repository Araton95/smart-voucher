jQuery( function( $ ) {
  //let web3 = new Web3('https://pumacy-vm1.westeurope.cloudapp.azure.com:9501/');
  let web3 = new Web3('https://mainnet.infura.io/v3/9be09333001949edb6c7189854dbb079');
  
  $(".obtain-wallet").click(function(e){
    e.preventDefault();
    let entropy = web3.utils.randomHex(32);
    (async () => {
      let resp = await web3.eth.accounts.create(entropy);
      await $("#wallet").val(resp.address);
      await $("#private_key").val(resp.privateKey);
    })();
  });
});