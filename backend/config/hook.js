module.exports = {
    settings: {
      'web3Controller': {
        enabled: true,
        wallet: process.env.WALLET,
        walletPk: process.env.WALLET_PK,
        provider: process.env.PROVIDER,
        contractAddress: process.env.CONTRACT_ADDRESS,
      },
      'encoder': {
        enabled: true,
        password: process.env.ENCODE_PASSWORD,
      },
    },
  };