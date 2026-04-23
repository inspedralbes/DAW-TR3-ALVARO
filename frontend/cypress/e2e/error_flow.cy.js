describe('Flux d\'Errors i Límits de Compra', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  it('Ha d\'impedir la compra si l\'usuari NO ha fet login prèviament', () => {
    // Navigate without authentication
    cy.contains('Comprar Entrades').click()
    cy.get('.seat-map-container').should('exist')

    // Click a seat
    cy.get('button[aria-label^="Seleccionar Fila"]').not('.opacity-50').not('.bg-primary').not('.bg-red-500').first().click({force: true})

    // Click checkout
    cy.contains('Procedir al Pagament').click()

    // Assuming the application intercepts and redirects to login because they are unauthenticated
    // or shows an error modal
    cy.url().should('include', '/login')
  })
})
