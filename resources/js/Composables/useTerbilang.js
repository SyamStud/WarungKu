// utils/terbilang.js

const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];
const belasan = ['sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas', 'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'];
const puluhan = ['', 'sepuluh', 'dua puluh', 'tiga puluh', 'empat puluh', 'lima puluh', 'enam puluh', 'tujuh puluh', 'delapan puluh', 'sembilan puluh'];

function terbilangRatus(angka) {
    const ratus = Math.floor(angka / 100);
    const sisaRatus = angka % 100;
    let hasil = '';

    if (ratus > 0) {
        if (ratus === 1) {
            hasil += 'seratus';
        } else {
            hasil += satuan[ratus] + ' ratus';
        }
    }

    if (sisaRatus > 0) {
        if (hasil !== '') {
            hasil += ' ';
        }
        if (sisaRatus < 10) {
            hasil += satuan[sisaRatus];
        } else if (sisaRatus < 20) {
            hasil += belasan[sisaRatus - 10];
        } else {
            const puluh = Math.floor(sisaRatus / 10);
            const sisa = sisaRatus % 10;
            hasil += puluhan[puluh];
            if (sisa > 0) {
                hasil += ' ' + satuan[sisa];
            }
        }
    }

    return hasil;
}

function useTerbilang(angka) {
    if (angka === 0) {
        return 'nol';
    }

    const pembilang = ['', 'ribu', 'juta', 'miliar', 'triliun'];
    let hasil = '';
    let index = 0;

    do {
        const ratusan = angka % 1000;
        if (ratusan !== 0) {
            let kata = terbilangRatus(ratusan);
            if (index > 0 && ratusan < 2 && index === 1) {
                kata = 'se' + pembilang[index];
            } else {
                kata += (kata !== '' && pembilang[index] ? ' ' : '') + pembilang[index];
            }
            hasil = kata + (hasil ? ' ' + hasil : '');
        }
        angka = Math.floor(angka / 1000);
        index++;
    } while (angka > 0);

    return hasil.trim() + ' rupiah';
}

export default useTerbilang;