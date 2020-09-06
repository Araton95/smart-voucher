'use strict'
const { sanitizeEntity } = require('strapi-utils')

/**
 * Read the documentation (https://strapi.io/documentation/v3.x/concepts/controllers.html#core-controllers)
 * to customize this controller
 */

module.exports = {
    async create(ctx) {
        try {
            const { wallet, website, email } = ctx.request.body

            if (!wallet) throw { message: 'Wallet is missing' }
            if (!website) throw { message: 'Website is missing' }
            if (!email) throw { message: 'Email is missing' }

            if (!strapi.hook.web3Controller.isAddress(wallet)) throw { message: 'Invalid wallet address' }
            if (!strapi.hook.helpers.isValidWebsite(website)) throw { message: 'Invalid website url' }
            if (!strapi.hook.helpers.isValidEmail(email)) throw { message: 'Invalid email' }

            const webshopByWallet = await strapi.services.webshop.findOne({ wallet })
            if (webshopByWallet) throw { message: 'Wallet already exist' }

            const webshopByWebsite = await strapi.services.webshop.findOne({ website })
            if (webshopByWebsite) throw { message: 'Website already exist' }

            const webshopByEmail = await strapi.services.webshop.findOne({ email })
            if (webshopByEmail) throw { message: 'Email already exist' }

            const entity = await strapi.services.webshop.create({ wallet, website, email })

            // Send 200 `ok`
            return sanitizeEntity(entity, { model: strapi.models.webshop })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(':').length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    },

    async findOne(ctx) {
        try {
            // User id is wallet
            const { id } = ctx.params
            if (!strapi.hook.web3Controller.isAddress(id)) throw { message: 'Invalid webshop address' }

            // Find webshop
            const webshop = await strapi.services.webshop.findOne({ 'wallet': id })

            // Validate webshop exist
            if (!webshop) {
                return ctx.badRequest(null, [{ messages: [{ id: 'Any webshop with provided wallet not found' }] }])
            }

            // Get webshop data from smart contract
            const { nonce, vouchersCount} = await strapi.hook.web3Controller.getWebshopData(id)

            const data = {
                ...webshop,
                nonce,
                vouchersCount
            }

            // Send 200 `ok`
            return sanitizeEntity(data, { model: strapi.models.webshop })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(':').length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    },

    async addPartner(ctx) {
        try {
            const { webshopAddr, partnerAddr, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: 'Webshop address is not found' }
            if (!partnerAddr) throw { message: 'Partner address is not found' }
            if (!nonce) throw { message: 'Nonce is not found' }
            if (!signature) throw { message: 'Signature is not found' }

            if (!strapi.hook.web3Controller.isAddress(webshopAddr)) throw { message: 'Invalid webshop address' }
            if (!strapi.hook.web3Controller.isAddress(partnerAddr)) throw { message: 'Invalid partner address' }
            if (!strapi.hook.web3Controller.isSignedData(signature)) throw { message: 'Invalid signed data' }

            // Find webshop
            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })

            // Validate webshop exist
            if (!webshop) {
                throw { message: 'Any webshop with provided wallet not found' }
            } else if (webshop.blocked) {
                throw { message: 'Webshop is blocked' }
            }

            // Find partner
            const partner = await strapi.services.webshop.findOne({ 'wallet': partnerAddr })

            // Validate partner exist
            if (!partner) {
                throw { message: 'Any webshop with provided partner wallet not found' }
            } else if (partner.blocked) {
                throw { message: 'Partner is blocked' }
            }

            await strapi.hook.web3Controller.addPartner({webshopAddr, partnerAddr, nonce, signature})

            const updatedPartners = JSON.stringify({ publishers: [...webshop.publishers, partner.id] })
            await strapi.services.webshop.update({ id: webshop.id }, updatedPartners)

            ctx.send({ ok: true })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(':').length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    },

    async removePartner(ctx) {
        try {
            const { webshopAddr, partnerAddr, nonce, signature } = ctx.request.body

            if (!webshopAddr) throw { message: 'Webshop address is not found' }
            if (!partnerAddr) throw { message: 'Partner address is not found' }
            if (!nonce) throw { message: 'Nonce is not found' }
            if (!signature) throw { message: 'Signature is not found' }

            if (!strapi.hook.web3Controller.isAddress(webshopAddr)) throw { message: 'Invalid webshop address' }
            if (!strapi.hook.web3Controller.isAddress(partnerAddr)) throw { message: 'Invalid partner address' }
            if (!strapi.hook.web3Controller.isSignedData(signature)) throw { message: 'Invalid signed data' }

            // Find webshop
            const webshop = await strapi.services.webshop.findOne({ 'wallet': webshopAddr })

            // Validate webshop exist
            if (!webshop) {
                throw { message: 'Any webshop with provided wallet not found' }
            } else if (webshop.blocked) {
                throw { message: 'Webshop is blocked' }
            }

            // Find partner
            const partner = await strapi.services.webshop.findOne({ 'wallet': partnerAddr })

            // Validate partner exist
            if (!partner) throw { message: 'Any webshop with provided partner wallet not found' }

            await strapi.hook.web3Controller.removePartner({webshopAddr, partnerAddr, nonce, signature})

            let updatedPartners = webshop.publishers.filter(el => el.id !== partner.id)
            updatedPartners = JSON.stringify({ publishers: updatedPartners })
            await strapi.services.webshop.update({ id: webshop.id }, publishers)

            ctx.send({ ok: true })
        } catch (error) {
            let { message } = error

            if (message && message.length && message.split(':').length > 1) {
                message = strapi.hook.helpers.trimContractError(message)
            }

            return ctx.badRequest(null, [{ message }])
        }
    },
}
