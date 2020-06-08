require('dotenv').config()
const HDWalletProvider = require('truffle-hdwallet-provider')

module.exports = {
  networks: {
    development: {
     host: "localhost",
     port: 8545,
     gasPrice: 0,
     network_id: "*"
    }
  },
  mocha: {
    timeout: 100000
  },
  compilers: {
    solc: {
      settings: {
       optimizer: {
         enabled: true,
         runs: 200
       },
      }
    }
  }
}
