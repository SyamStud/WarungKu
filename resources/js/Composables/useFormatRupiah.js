// src/composables/useFormatRupiah.js

export function useFormatRupiah() {
  const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(value);
  };

  return {
    formatRupiah,
  };
}
