/**
 * Test values for the FIBAQ pilot registration form (pages 3066 / 3644).
 *
 * Reached as ?dev=form&devid=pilot-registration — devid is this file's name. Copy
 * to pilot-registration.<variant>.js for a second dataset against the same form.
 *
 * crew-name2/3 live in conditional groups and are only filled when those groups
 * are open; the filler skips them otherwise rather than posting orphan values.
 */

import { file } from 'pb/dev/utils.js';


export default {

	//piloto
	'pilot-firstname': 'Tiago',
	'pilot-lastname': 'Teste Simões',
	'pilot-address1': 'Rua das Amoreiras 148',
	'pilot-address2': '3.º Esq.',
	'pilot-city': 'Lisboa',
	'pilot-country': 'Portugal',
	'pilot-cep': '1250-097',
	'pilot-phone': '+351 912 345 678',
	'pilot-email': 'dev+pilot@publibalao.test',
	'pilot-id': '12345678 9 ZZ0',

	//experiência
	'pilot-experience': '14 anos, 320 voos em AX-7 e AX-8',
	'pilot-ax-pic-hours': '480',
	'pilot-license': file('licenca-piloto.pdf'),
	'pilot-medical-exam': file('exame-medico.pdf'),

	//balão
	'balloon-owner-name': 'Clube de Balonismo de Teste',
	'balloon-registration': 'CS-TST',
	'balloon-type': 'AX-8',
	'balloon-manufacturer': 'Ultramagic',
	'balloon-sponsor': 'Patrocinador de Teste, Lda.',
	'balloon-passengers-number': '3',
	'balloon-photo': file('balao.png'),
	'balloon-certificate': file('certificado-navegabilidade.pdf'),
	'balloon-certificate-registration': file('certificado-matricula.pdf'),
	'balloon-arc': file('arc.pdf'),
	'balloon-insurance-company': 'Seguradora de Teste',
	'balloon-insurance': file('seguro.pdf'),
	'balloon-insurance-apolice-number': 'AP-2026-000123',
	'balloon-insurance-apolice-expiration-date': '31-12-2027',

	//equipa
	'crew-name1': 'Ana Resgate',
	'crew-name2': 'Bruno Resgate',
	'crew-name3': 'Carla Resgate',
	'crew-room-type-number-single': '1',
	'crew-room-type-number-couple': '0',
	'crew-room-type-number-single-double': '1',
	'crew-room-type-number-triple': '0',

	//the form pins these to the event window; the filler clamps to the input's own
	//min/max, so they follow if the dates move
	'date-checkin': '2026-11-06',
	'date-checkout': '2026-11-13',

	//fatura
	'invoice': 'Sim',
	'invoice-name': 'Clube de Balonismo de Teste',
	'invoice-vat': '500123456',
	'invoice-address': 'Rua das Amoreiras 148, 1250-097 Lisboa',

	//conclusão
	'comments': 'Inscrição de teste — gerada pelo módulo dev, ignorar.',
	'consent': true

};
