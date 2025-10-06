# Hetzner DNS Console: Análise completa para domínios .com.br

**A Hetzner oferece um serviço DNS gerenciado completamente gratuito que suporta registros wildcard, tornando-se a solução ideal para seu caso de uso.** Diferente do DNS gratuito do Registro.br que não suporta wildcards, o Hetzner DNS Console permite criar registros como *.seudominio.com.br sem custo adicional, com integração perfeita ao seu servidor já hospedado na Hetzner. O serviço está disponível tanto para clientes Hetzner quanto para não-clientes, oferecendo interface intuitiva, API REST completa e confiabilidade adequada para projetos de pequeno a médio porte focados na Europa e Brasil.

Esta análise demonstra que **migrar do Registro.br para o Hetzner DNS é não apenas viável, mas recomendado** para seu cenário específico, representando a melhor relação custo-benefício comparado ao Cloudflare ou permanecer no DNS do Registro.br.

## Serviço DNS gerenciado da Hetzner existe e funciona perfeitamente

**Sim, a Hetzner oferece um serviço DNS gerenciado completo chamado DNS Console**, disponível gratuitamente em dns.hetzner.com desde 2020. O serviço funciona como uma plataforma autoritativa de gerenciamento DNS que permite controlar zonas e registros através de interface web e API REST.

O funcionamento é direto: você cria uma zona DNS no console da Hetzner, adiciona os registros necessários (A, AAAA, CNAME, MX, TXT, etc.), e aponta os nameservers do seu domínio para os servidores da Hetzner (hydrogen.ns.hetzner.com, oxygen.ns.hetzner.com e helium.ns.hetzner.de). A partir daí, a Hetzner passa a responder todas as consultas DNS do seu domínio.

**Diferencial importante**: o serviço inclui uma ferramenta de auto-scanning que detecta automaticamente registros DNS existentes na internet, importando-os para facilitar migrações e reduzir erros de configuração manual. Este recurso economiza tempo significativo durante a transição de outros provedores DNS.

O DNS Console integra-se perfeitamente com outros serviços Hetzner através de Single Sign-On (SSO), permitindo gerenciar DNS, Cloud e servidores dedicados em uma única conta. Não há necessidade de ser cliente Hetzner para usar o serviço DNS — qualquer pessoa pode criar uma conta gratuita.

## Suporte a wildcards confirmado e amplamente testado

**A Hetzner DNS suporta completamente registros wildcard (*), incluindo registros A para domínios .com.br**, resolvendo exatamente sua necessidade atual. Esta confirmação vem de três fontes independentes: documentação oficial, implementações práticas em produção e tutoriais oficiais.

A documentação oficial da Hetzner para registros AAAA afirma explicitamente que "você pode usar registros AAAA de forma similar a registros A, incluindo configurações at-records e **configurações wildcard**". Um exemplo prático verificado no GitHub mostra registros wildcard funcionando em produção: `A *.vps.5xx.engineer 100.123.179.103`.

**Para seu domínio .com.br, você poderá criar**:
- `*.seudominio.com.br` → endereço IPv4 do servidor (registro A)
- `*.subdominio.seudominio.com.br` → endereço específico (registro A)
- `*.seudominio.com.br` → endereço IPv6 (registro AAAA)

O suporte a wildcards da Hetzner é tão robusto que permite até obter certificados SSL/TLS wildcard do Let's Encrypt usando desafio DNS-01, conforme demonstrado em tutorial oficial da comunidade Hetzner. Usuários reportam que certificados como `*.exemplo.com` funcionam perfeitamente com validação automática via API do Hetzner DNS.

**Importante**: o DNS gratuito do Registro.br **não suporta** registros wildcard, tornando esta migração não apenas recomendada, mas necessária para seu caso de uso.

## Recursos e funcionalidades abrangentes para a maioria dos casos

O Hetzner DNS oferece um conjunto robusto de 15 tipos de registros DNS e funcionalidades modernas de automação e gerenciamento.

**Tipos de registros suportados via interface gráfica**: A (IPv4), AAAA (IPv6), CNAME (aliases), MX (email), NS (nameservers), SRV (serviços), TXT (texto/verificação). Registros adicionais como CAA, PTR, DS, TLSA, DANE, HINFO, SOA e RP podem ser criados manualmente editando arquivos de zona BIND.

**Funcionalidades principais incluem**:

A **ferramenta de importação automática** escaneia a internet para descobrir registros DNS existentes, importando-os automaticamente sem necessidade de copiar e colar manualmente. Este recurso evita erros comuns de migração e acelera significativamente o processo de configuração inicial.

A **API REST completa** oferece documentação detalhada com exemplos de código em PHP, Python e Go, disponível em dns.hetzner.com/api/v1/. Todos os recursos da interface web estão disponíveis via API, permitindo automação completa através de tokens de acesso pessoais. A API suporta integração com ferramentas modernas como Ansible (módulos community.dns), Terraform, certbot para certificados SSL automáticos, e projetos de DynDNS.

**Gerenciamento de zonas** permite criar, visualizar, editar e deletar zonas DNS, importar arquivos de zona formato BIND, editar zone files diretamente, e verificar propriedade de domínios usando método de registro TXT. O limite padrão é 25 zonas por conta, facilmente expandível abrindo ticket de suporte com justificativa.

**Operações com registros** incluem criação, atualização e deleção individual ou em massa, suporte para registros root (@) e wildcard (*), gerenciamento completo de subdomínios dentro das zonas. Múltiplos registros do mesmo tipo podem coexistir para redundância.

**Infraestrutura de nameservers** distribuída em múltiplas localizações oferece redundância através de três servidores autoritativos principais na Alemanha. O serviço também suporta transferências de zona AXFR, permitindo que servidores DNS secundários sincronizem zonas para redundância adicional.

## Interface intuitiva com curva de aprendizado mínima

A interface do Hetzner DNS Console é consistentemente elogiada pela **simplicidade e facilidade de uso**, especialmente comparada a soluções enterprise mais complexas como AWS Route53.

**O console web** apresenta design limpo com botão amarelo destacado "Add new zone" para criação rápida de zonas. A navegação é direta: após login em dns.hetzner.com, você visualiza todas as zonas, clica em uma para ver/editar registros, e adiciona novos registros através de formulários simples com campos para tipo, nome, valor e TTL.

Usuários relatam experiências positivas: "Minha experiência geral com a nova ferramenta da Hetzner tem sido boa até agora... é fácil alternar entre os consoles DNS e Cloud. Depois que mudei os nameservers, meus sites ficaram acessíveis pois a resolução de nome de domínio é rápida." Outro usuário nota: "Funciona muito bem... ao fazer alterações em zonas no Virtualmin, os nameservers da Hetzner foram atualizados praticamente instantaneamente."

**Três métodos de importação** facilitam migrações: auto-scanning (detecção automática de registros existentes), importação de arquivo de zona BIND, ou DNS secundário via protocolo AXFR. A opção de auto-scanning é particularmente útil para iniciantes, reduzindo erros de configuração manual.

**Single Sign-On integrado** significa que se você já tem conta Hetzner Cloud ou Robot, usa as mesmas credenciais para acessar o DNS Console, eliminando necessidade de múltiplos logins. A interface está disponível em inglês e alemão, mas não em português.

**Limitações de usabilidade**: registros SOA não podem ser editados diretamente via API, requerendo método alternativo de importação de arquivo BIND. O serviço impõe TTL mínimo de 60 segundos para todos os registros. A interface é simples demais para recursos avançados como geo-routing ou load balancing, que não existem no serviço.

## Performance adequada para Europa e Brasil, não para audiência global

O Hetzner DNS oferece **performance sólida para usuários europeus e latino-americanos**, mas não compete com provedores globais anycast como Cloudflare em termos de latência mundial.

**Tempos de resposta DNS**: usuários europeus reportam resolução "extremamente rápida" com latências aceitáveis. Testes de terceiros mostram o Hetzner DNS mais lento que CloudNS mas ainda dentro de padrões aceitáveis. Um usuário da Escandinávia observou: "Para mim, meus 'clientes' estão na Escandinávia e um usuário médio não nota diferença entre servidores da Cloudflare em Oslo vs Hetzner na Alemanha."

**Distribuição geográfica limitada**: esta é a **principal limitação de performance**. A Hetzner não utiliza rede anycast — os servidores DNS estão localizados principalmente na Alemanha (múltiplos datacenters Hetzner) com terceira localização na MyLoc. Isto significa latências mais altas para usuários na Ásia, partes das Américas e outras regiões distantes.

Para seu caso (servidor na Hetzner servindo usuários no Brasil), a latência Alemanha-Brasil é aceitável para a maioria das aplicações web. A propagação DNS secundária acontece "poucos segundos após BIND enviar a notificação", demonstrando sincronização eficiente.

**Confiabilidade e uptime**: o StatusGator rastreou 731 incidentes desde março de 2021 (4+ anos de monitoramento), mas muitos foram manutenções programadas. Usuários reportam anos de operação consistente. A Hetzner mantém página de status em status.hetzner.com com relatórios de incidentes transparentes.

**Problema técnico crítico identificado**: servidores DNS autoritativos da Hetzner não suportam consultas recursivas para domínios apontando de volta para IPs Hetzner, podendo causar falhas em ferramentas como cert-manager ou CoreDNS Kubernetes rodando dentro da infraestrutura Hetzner. Solução: usar resolvedores externos (1.1.1.1, 8.8.8.8) para necessidades recursivas.

**Sem SLA ou garantias**: sendo serviço gratuito, não há Service Level Agreement ou garantias de uptime. Para ambientes mission-critical que exigem 99.99% de disponibilidade contratual, considerar alternativas pagas com SLA.

## Custo zero absoluto para todos os usuários

**O Hetzner DNS Console é completamente gratuito sem custos ocultos, taxas ou limitações de freemium.** Esta é uma das vantagens mais significativas do serviço.

A documentação oficial afirma: "No momento, o DNS Console está disponível sem custo extra. Você pode usar o DNS Console com sua conta atual Robot ou Cloud. Para não-clientes Hetzner, você pode criar uma conta gratuita para usar o DNS Console."

**Estrutura de preços detalhada**:
- Taxa de configuração: €0.00
- Mensalidade/anuidade: €0.00  
- Por zona DNS: €0.00
- Por registro DNS: €0.00
- Por consulta DNS (queries): €0.00
- Acesso à API: €0.00
- Aumento de limite de zonas: €0.00 (baseado em aprovação via ticket)

**Disponibilidade universal**: você NÃO precisa ser cliente Hetzner para usar o serviço. Qualquer pessoa pode criar conta gratuita em dns.hetzner.com e começar a usar imediatamente. O serviço está disponível para clientes Hetzner Robot (servidores dedicados), Hetzner Cloud, konsoleH e não-clientes.

**Limites e quotas do serviço gratuito**:

Zonas DNS têm limite padrão de **25 zonas por conta**, facilmente aumentável abrindo ticket de suporte explicando a necessidade. Não há indicação de máximo absoluto ou cobranças por limites aumentados.

Registros DNS não têm limite explícito por zona na documentação oficial. TTL mínimo de 60 segundos é obrigatório para todos os registros.

Rate limiting da API é aproximadamente **42 requisições por minuto**, com headers de resposta informando limite total, requisições restantes e tempo de reset. Retorna HTTP 429 quando limite excedido, mas permite bursts de requisições.

**Sem limites em**: consultas DNS (resolução de usuários finais), largura de banda para tráfego DNS, disponibilidade geográfica, número de registros por zona.

**Comparação com alternativas pagas**: diferente de AWS Route 53 ($0.50/zona + cobranças por query) ou Google Cloud DNS ($0.20/zona + queries), o Hetzner não cobra nada. Não é modelo freemium como alguns concorrentes — não existem tiers pagos ou recursos premium bloqueados. É genuinamente gratuito sem pressão de upgrade.

## Migração do Registro.br para Hetzner DNS é direta mas requer planejamento

Migrar seu domínio .com.br do DNS do Registro.br para usar nameservers da Hetzner é um **processo de mudança de nameservers, não transferência de registro**. Seu domínio permanece registrado no Registro.br — apenas o serviço DNS muda.

**Processo passo a passo (versão resumida)**:

**Fase 1 - Preparação (não mude nameservers ainda)**:

Documente todos os registros DNS atuais no Registro.br, incluindo A, AAAA, MX, TXT, CNAME, etc. Tire screenshots como backup. Este passo é crítico pois registros MX (email) esquecidos causam interrupção de email.

Reduza valores TTL para 300 segundos (5 minutos) no Registro.br e **aguarde 24-48 horas** para o TTL antigo expirar globalmente. Este passo permite propagação mais rápida durante a migração e rollback mais ágil se necessário.

Crie conta e zona no Hetzner DNS Console em dns.hetzner.com, inserindo seu domínio (exemplo.com.br). Escolha método "Add records" para ter controle total.

Recrie TODOS os registros DNS da documentação do Registro.br no Hetzner, incluindo o registro wildcard crítico: Tipo A, Nome `*`, Valor [IP do seu servidor Hetzner], TTL 3600.

Verifique linha por linha se registros no Hetzner correspondem exatamente aos do Registro.br. Teste registros críticos usando ferramentas como `dig @hydrogen.ns.hetzner.com seudominio.com.br` antes de prosseguir.

**Fase 2 - Mudança de nameservers**:

Entre no Registro.br, vá em seu domínio → seção DNS → "Alterar servidores DNS". Selecione "Usar outros servidores" (NÃO selecione "Utilizar DNS do Registro.br").

Remova nameservers existentes (a.dns.br, b.dns.br, etc.) e adicione os nameservers da Hetzner: Servidor Master `hydrogen.ns.hetzner.com`, Servidor Slave 1 `oxygen.ns.hetzner.com`, Servidor Slave 2 `helium.ns.hetzner.de`.

**Nota importante**: Registro.br tipicamente requer ID Administrativo do domínio para autorizar mudanças de nameserver. Esta alteração é processada em minutos a horas pelo Registro.br.

**Fase 3 - Monitoramento (24-72 horas)**:

Monitore propagação DNS usando whatsmydns.net ou comandos `dig seudominio.com.br @8.8.8.8` e `dig *.seudominio.com.br @8.8.8.8` para testar wildcard.

Teste serviços críticos: acessibilidade do site (domínio principal e www), funcionalidade de subdomínios wildcard, envio/recebimento de email (registros MX), integrações API ou verificações de domínio.

ISPs brasileiros (Vivo, Claro, Oi, NET) às vezes mantêm cache DNS além do TTL sugerido. Planeje período de observação de 72 horas para garantir estabilidade.

Após 48-72 horas de estabilidade, aumente TTL de volta para 3600 ou 86400 segundos no Hetzner DNS Console.

**Considerações especiais para domínios .br**:

Se DNSSEC estiver habilitado no Registro.br, **desabilite-o antes** de mudar nameservers, pois Hetzner não suporta DNSSEC atualmente. Manter DNSSEC ativo causará falha completa de resolução DNS.

A interface do Registro.br é apenas em português. Termos importantes: "Alterar servidores DNS" (alterar nameservers), "Usar outros servidores" (selecione esta opção).

Registros de email (MX) são prioridade máxima durante migração. Teste email minuciosamente pós-migração pois muitos serviços brasileiros usam email para verificação de domínio.

**Tempo total**: mudança de nameserver propaga em 2-48 horas, mas planejar 72 horas de monitoramento. Para migração segura e monitorada, cronograma completo de 2-4 semanas é recomendado, começando com preparação, esperando expiração de TTL, executando migração e estabilizando.

**Procedimento de rollback**: se problemas ocorrerem, volte ao Registro.br imediatamente e mude nameservers de volta para originais (a.dns.br, b.dns.br, etc.). Propagação levará o tempo completo do TTL novamente.

## Hetzner DNS é a melhor escolha para seu cenário específico

Dadas suas circunstâncias (servidor hospedado na Hetzner + necessidade de DNS wildcard para domínio .com.br), **a recomendação primária é migrar para Hetzner DNS**, com Cloudflare como alternativa viável apenas em cenários específicos.

**Análise comparativa das três opções**:

**Registro.br DNS** deve ser descartado imediatamente pois **não suporta registros wildcard** — esta é uma limitação definitiva que torna impossível atender sua necessidade. Além disso, oferece tipos de registro limitados, sem acesso API, sem recursos avançados e interface básica. Mantenha seu domínio registrado no Registro.br (obrigatório para domínios .br), mas não use o DNS deles.

**Hetzner DNS** é a escolha ideal por cinco razões principais:

Integração perfeita com seu servidor Hetzner existente significa gerenciamento unificado de conta (SSO), sistema de suporte consolidado, melhor integração para certificados SSL/TLS, faturamento simplificado e proximidade geográfica (DNS e hosting na mesma rede reduzem latência).

Atende completamente requisito de wildcard que o Registro.br não oferece, permitindo `*.seudominio.com.br` sem limitações.

Custo zero absoluto sem pressão de upgrade ou recursos bloqueados por paywall, diferente de modelos freemium que tentam convertê-lo para planos pagos.

Recursos suficientes para necessidades padrão de hosting incluindo todos os tipos de registro necessários, API para automação, performance confiável e gerenciamento fácil.

Compatibilidade total com domínios .br sem restrições especiais ou configurações adicionais.

**Cloudflare DNS** é tecnicamente superior em alguns aspectos mas adiciona complexidade desnecessária para seu caso:

Escolha Cloudflare **somente se** você precisa de tráfego global com usuários principalmente fora do Brasil, proteção DDoS avançada além do nível de hosting, funcionalidade CDN integrada, DNSSEC imediatamente (ainda não disponível na Hetzner), analytics avançados e monitoramento detalhado, múltiplos provedores de hosting (independência de DNS), load balancing entre regiões, ou Workers/edge computing.

Para uso padrão (site/aplicação hospedada na Hetzner servindo usuários brasileiros/europeus), Cloudflare adiciona um provedor separado a gerenciar, aumenta overhead de configuração, e oferece recursos que você provavelmente não precisará.

**Tabela de decisão rápida**:

| Critério | Hetzner DNS | Cloudflare DNS | Registro.br DNS |
|----------|-------------|----------------|-----------------|
| Suporte wildcard | ✅ Sim | ✅ Sim | ❌ **Não** |
| Custo | ✅ Grátis | ✅ Grátis (básico) | ✅ Grátis |
| Integração Hetzner | ✅ Perfeita | ⚠️ Separado | ⚠️ Separado |
| DNSSEC | ❌ Não | ✅ Sim | ✅ Sim |
| Performance Brasil/Europa | ✅ Boa | ✅ Excelente | ⚠️ Variável |
| API completa | ✅ Sim | ✅ Sim | ❌ Limitada |
| Complexidade | ✅ Simples | ⚠️ Média | ✅ Simples |
| Adequado para seu caso | ✅ **Ideal** | ⚠️ Viável mas excessivo | ❌ Inadequado |

**Recomendação final**: migre para **Hetzner DNS**. Atende perfeitamente seus requisitos (wildcard + hosting Hetzner) com custo zero, integração ideal e simplicidade. Reserve Cloudflare apenas se eventualmente precisar recursos avançados como CDN global ou proteção DDoS robusta.

**Abordagem híbrida não recomendada**: usar Cloudflare na frente da Hetzner (proxied) adiciona complexidade significativa e camadas desnecessárias de configuração, apropriado apenas se enfrentando ameaças de segurança específicas ou tráfego massivo global.

## Conclusão

O Hetzner DNS Console resolve completamente sua limitação atual com o DNS do Registro.br ao oferecer suporte total a registros wildcard sem custo algum. O serviço é robusto, confiável e integra-se perfeitamente com sua infraestrutura existente na Hetzner, tornando-se a escolha mais lógica e eficiente.

**Próximos passos recomendados**:

Inicie hoje documentando todos os registros DNS atuais no Registro.br, preparando inventário completo. Crie conta no Hetzner DNS Console em dns.hetzner.com esta semana, configurando zona de teste com registros atuais mais o wildcard necessário. Reduza TTL para 300 segundos no Registro.br e aguarde 48 horas antes de executar migração. Execute mudança de nameservers no início da semana durante período de baixo tráfego, monitorando intensivamente por 72 horas.

A migração é direta e de baixo risco quando executada metodicamente, com benefício imediato de funcionalidade wildcard que atualmente lhe falta completamente.