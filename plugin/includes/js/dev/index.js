/**
 * Dev routine dispatcher.
 *
 * Splits "what to run" from "what to run it on": ?dev= names an agnostic routine
 * that ships with this plugin, ?devid= names the caller's data for it. That keeps
 * the routines reusable across sites while the specifics stay with whoever owns
 * them — this module never goes looking for a dataset, it asks for one.
 *
 *   ?dev=form&devid=pilot-registration
 */


//?devid= ends up inside the caller's import path, so hold it to a plain file name
const ID = /^[a-z0-9][a-z0-9.-]*$/i;


/**
 * Run a dev routine if the query string asks for this one.
 *
 * Returns without loading anything when ?dev= names a different routine or none
 * at all, so a call site costs one URLSearchParams read in normal use.
 *
 * @param {string} routine Name this call answers to, matched against ?dev=.
 * @param {object} context Passed through to the routine.
 * @param {Function} [context.data] Resolver for ?devid=, returning a module or
 *                                  its default export. Required by routines that
 *                                  take a dataset.
 * @return {Promise<*>} Whatever the routine returns, or undefined if it did not run.
 */
export async function dev(routine, context = {}) {

	const params = new URLSearchParams(location.search);

	if (params.get('dev') !== routine) {
		return;
	}

	const id = params.get('devid');

	if (id !== null && !(ID.test(id) && !id.includes('..'))) {

		console.error(`[pb/dev] rejected devid "${id}"`);

		return;

	}

	try {

		const { run } = await import(`./routines/${routine}.js`);

		//resolvers commonly are `id => import(...)`, so unwrap a module namespace
		const resolved = id !== null && context.data ? await context.data(id) : undefined;

		return await run({
			...context,
			id,
			params,
			data: resolved?.default ?? resolved
		});

	} catch (error) {

		console.error(`[pb/dev] "${routine}"${id ? ` / "${id}"` : ''} failed`, error);

	}

}
