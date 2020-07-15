require('dotenv').config()
const HDWalletProvider = require('truffle-hdwallet-provider')

// Seed of your account
const mnemonic = process.env.DEPLOYER_MNEMONIC_PHASE
const provider = process.env.POA_PRODUCTION_PROVIDER

module.exports = {
  networks: {
    "development": {
     host: "127.0.0.1",
     port: 8545,
     gasPrice: 0,
     network_id: "*"
    },
    "poa":  {
      provider: new HDWalletProvider(mnemonic, provider, 0, 2),
      gasLimit: 10000000,
      gasPrice: 0,
      network_id: '*'
    },
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
