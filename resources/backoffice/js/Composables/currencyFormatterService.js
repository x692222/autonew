export const formatCurrency = (number, decimals = 0) => {
  if(typeof number === 'undefined' || number === null) {
    return null;
  }
  number = (Math.round(number * 100) / 100).toFixed(2);
  const natural = n => `${n}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  return natural((Math.round(number * 100) / 100).toFixed(decimals))
};
