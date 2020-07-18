const crypto = require('crypto')


module.exports = strapi => {
  const algorithm = 'aes256'
  const password = strapi.config.get('hook.settings.encoder').password

  const hook = {
    // --------
    // Defaults
    // --------

    defaults: {},

    async initialize() {},

    // --------
    // GETTERS
    // --------

    encode (data) {
      const cipher = crypto.createCipher(algorithm, password)
      let encrypted = cipher.update(data, 'utf8', 'hex')
      encrypted += cipher.final('hex')
      return encrypted
    },

    decode (data) {
      const decipher = crypto.createDecipher(algorithm, password)
      let decrypted = decipher.update(data, 'hex', 'utf8')
      decrypted += decipher.final('utf8')
      return decrypted
    }
  }

  return hook
}