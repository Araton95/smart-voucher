const crypto = require('crypto')


module.exports = strapi => {
  const algorithm = 'aes256'
  const password = strapi.config.get('hook.settings.encoder').password

  const hook = {
    // --------
    // Defaults
    // --------

    defaults: {
      cipher: crypto.createCipher(algorithm, password),
      decipher: crypto.createDecipher(algorithm, password)
    },

    async initialize() {},

    // --------
    // GETTERS
    // --------

    encode (data) {
      let encrypted = this.defaults.cipher.update(data, 'utf8', 'hex')
      encrypted += this.defaults.cipher.final('hex')
      return encrypted
    },

    decode (data) {
      let decrypted = this.defaults.decipher.update(data, 'hex', 'utf8')
      decrypted += this.defaults.decipher.final('utf8')
      return decrypted
    }
  }

  return hook
}