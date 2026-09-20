<?php

namespace App\Support;

class ChatbotPrivacyGuard
{
    private const PRIVACY_MESSAGE = 'Não possuo acesso aos dados pessoais, familiares ou de saúde dos beneficiários. Consulte essas informações diretamente nas telas autorizadas da aplicação, respeitando as permissões do seu perfil.';

    /**
     * @var list<string>
     */
    private const SENSITIVE_VALUE_PATTERNS = [
        '/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/u',
        '/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/iu',
        '/(?:\+?55\s*)?(?:\(?\d{2}\)?\s*)?9?\d{4}[\s.-]?\d{4}/u',
        '/\b(?:rua|avenida|av\.?|travessa|rodovia|estrada|alameda)\s+[^\r\n,]{2,80}(?:,\s*)?\d+\b/ui',
    ];

    /**
     * @var list<string>
     */
    private const PERSONAL_DATA_REQUEST_PATTERNS = [
        '/\b(?:qual|quais|mostre|exiba|informe|busque|procure|liste|diga|revele)\b.{0,100}\b(?:cpf|telefone|endere[cç]o|e-?mail|sa[uú]de|defici[eê]ncia|diagn[oó]stico|hist[oó]rico|dados pessoais|fam[ií]lia)\b/ui',
        '/\b(?:cpf|telefone|endere[cç]o|e-?mail|problemas? de sa[uú]de|defici[eê]ncia|diagn[oó]stico|hist[oó]rico)\b.{0,80}\b(?:do|da|de um|de uma|de determinado|de determinada)\b/ui',
        '/\b(?:benefici[aá]rio|benefici[aá]ria|pessoa|fam[ií]lia)\s+(?:espec[ií]fic[oa]|determinad[oa]|chamad[oa])\b/ui',
        '/\bbenefici[aá]ri[oa]\s+[A-ZÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇ][\p{L}\'’-]{1,}\b/u',
        '/\b(?:possui|tem)\b.{0,80}\b(?:problemas? de sa[uú]de|defici[eê]ncia|doen[cç]a|diagn[oó]stico)\b/ui',
    ];

    public function responseFor(string $message): ?string
    {
        foreach (self::SENSITIVE_VALUE_PATTERNS as $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return self::PRIVACY_MESSAGE;
            }
        }

        foreach (self::PERSONAL_DATA_REQUEST_PATTERNS as $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return self::PRIVACY_MESSAGE;
            }
        }

        return null;
    }
}
