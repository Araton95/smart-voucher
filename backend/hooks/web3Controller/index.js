const Web3 = require('web3')
const abi = require('./abi.json')

module.exports = strapi => {
  // const { provider } = strapi.config.get('hook.settings.web3Controller')
  const web3 = new Web3('http://127.0.0.1:8545')

  const hook = {
    // --------
    // Defaults
    // --------

    defaults: {
      contract: new web3.eth.Contract(abi, strapi.config.get('hook.settings.web3Controller').contractAddress),
    },

    async initialize() {

    },

    // --------
    // SETTERS
    // --------

    async createVoucher(data) {
      try {
        const { webshopAddr, amount, nonce, signature } = data
        const contractMethod = this.defaults.contract.methods.create(webshopAddr, amount, nonce, signature)
        await this._signAndSend(contractMethod)
      } catch (error) {
        throw error
      }
    },

    async redeemVoucher(data) {
      try {
        const { webshopAddr, amount, voucherId, nonce, signature } = data
        const contractMethod = this.defaults.contract.methods.redeem(webshopAddr, amount, voucherId, nonce, signature)
        await this._signAndSend(contractMethod)
      } catch (error) {
        throw error
      }
    },

    async addPartner(data) {
      try {
        const { webshopAddr, partnerAddr, nonce, signature } = data
        const contractMethod = this.defaults.contract.methods.addPartner(webshopAddr, partnerAddr, nonce, signature)
        await this._signAndSend(contractMethod)
      } catch (error) {
        throw error
      }
    },

    async removePartner(data) {
      try {
        const { webshopAddr, partnerAddr, nonce, signature } = data
        const contractMethod = this.defaults.contract.methods.removePartner(webshopAddr, partnerAddr, nonce, signature)
        await this._signAndSend(contractMethod)
      } catch (error) {
        throw error
      }
    },

    // --------
    // PRIVATE
    // --------

    async signMethod(contractMethod) {
      try {
        const serverWallet = strapi.config.get('hook.settings.web3Controller').wallet
        const serverWalletPK = strapi.config.get('hook.settings.web3Controller').walletPk
        const contractAddress = strapi.config.get('hook.settings.web3Controller').contractAddress

        const nonce = await web3.eth.getTransactionCount(serverWallet, 'pending')
        const encodedABI = contractMethod.encodeABI()
        const estimatedGas = await contractMethod.estimateGas({
          'from': serverWallet,
          'to':   contractAddress
        })

        const rawTransaction = {
          'nonce':    web3.utils.toHex(nonce),
          'from':     serverWallet,
          'to':       contractAddress,
          'gasPrice': '0x0',
          'gasLimit': web3.utils.toHex(estimatedGas),
          'value':    '0x0',
          'data':     encodedABI
        }

        const privateKey = new Buffer(serverWalletPK.substr(2), 'hex')
        const tx = new EthereumTx(rawTransaction)
        tx.sign(privateKey)
        const serializedTx = tx.serialize()

        return new Promise((resolve, reject) => {
          web3.eth.sendSignedTransaction('0x' + serializedTx.toString('hex'))
          .on('confirmation', () => resolve())
          .on('error', error => reject(error))
        })
      } catch (error) {
        throw error
      }
    },

    // --------
    // GETTERS
    // --------

    async getWeb3() {
      return web3
    },

    async getVouchersCount() {
      try {
        return await this.defaults.contract.methods.getLastId().call()
      } catch (error) {
        throw error
      }
    },

    async getVoucherData(voucherId) {
      try {
        return await this.defaults.contract.methods.getVoucherData(voucherId).call()
      } catch (error) {
        throw error
      }
    },

    async getWebshopData(address) {
      try {
        return await this.defaults.contract.methods.getWebshopData(address).call()
      } catch (error) {
        throw error
      }
    },

    async getVoucherByWebshop(address, order) {
      try {
        return await this.defaults.contract.methods.getVoucherByWebshop(address, order).call()
      } catch (error) {
        throw error
      }
    },

    async isWebshopExist(address) {
      try {
        return await this.defaults.contract.methods.isWebshopExist(address).call()
      } catch (error) {
        throw error
      }
    },

    async isWebshopPartner(address, partner) {
      try {
        return await this.defaults.contract.methods.isWebshopPartner(address, partner).call()
      } catch (error) {
        throw error
      }
    },

    async isVoucherOwnedByWebshop(address, voucherId) {
      try {
        return await this.defaults.contract.methods.isVoucherOwnedByWebshop(address, voucherId).call()
      } catch (error) {
        throw error
      }
    },
  };

  return hook;
};