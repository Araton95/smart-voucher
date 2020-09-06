
module.exports = () => {
  const hook = {
    // --------
    // Defaults
    // --------

    defaults: {},

    async initialize() {},

    // --------
    // GETTERS
    // --------

    centsToUsd(amount) {
      amount = Number(amount) // Convert argument to number
      amount /= 100 // Divide cents to USD
      amount = parseFloat(amount).toLocaleString('en-US', { style: 'currency', currency: 'USD' })
      amount = amount.substring(1) // remove $ symbol at the start
      return amount
    },

    isValidEmail(email) {
      const regex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
      return regex.test(String(email).toLowerCase())
    },

    isValidWebsite(url) {
      const regex=/^((https?|ftp|smtp):\/\/)?(www.)?[a-z0-9]+(\.[a-z]{2,}){1,3}(#?\/?[a-zA-Z0-9#]+)*\/?(\?[a-zA-Z0-9-_]+=[a-zA-Z0-9-%]+&?)?$/
      return regex.test(String(url).toLowerCase())
    },

    trimContractError(message) {
      const trimmedMessage = message.split(':')[message.split(':').length - 1].trim()
      return trimmedMessage
    }
  }

  return hook
}