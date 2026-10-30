document.addEventListener('DOMContentLoaded', function () {
    const formatCEP = (value) => value
        .replace(/\D/g, '')
        .slice(0, 8)
        .replace(/(\d{5})(\d)/, '$1-$2');

    const formatCNPJ = (value) => value
        .replace(/\D/g, '')
        .slice(0, 14)
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\.\d{3})(\d)/, '$1.$2')
        .replace(/(\.\d{3}\.\d{3})(\d)/, '$1/$2')
        .replace(/(\/\d{4})(\d)/, '$1-$2');

    const formatTelefone = (value) => {
        const digits = value.replace(/\D/g, '').slice(0, 11);
        if (digits.length <= 10) {
            return digits.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
        }
        return digits.replace(/(\d{2})(\d{5})(\d)/, '($1) $2-$3');
    };

    document.querySelectorAll('.cep').forEach((input) => {
        input.addEventListener('input', function () {
            this.value = formatCEP(this.value);
        });
    });

    document.querySelectorAll('.cnpj').forEach((input) => {
        input.addEventListener('input', function () {
            this.value = formatCNPJ(this.value);
        });
    });

    document.querySelectorAll('.telefone').forEach((input) => {
        input.addEventListener('input', function () {
            this.value = formatTelefone(this.value);
        });
    });
});
