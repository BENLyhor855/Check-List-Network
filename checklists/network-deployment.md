# Network deployment checklist

Use with the [site record](../templates/site-record.md). Agree expected outcomes and test sources/destinations before making changes. Record pass, fail, blocked, or not applicable (with a reason) in the record; check a box only after its outcome is verified. Keep evidence in an approved private location. Obtain authorization and a change window before touching production. A failed or blocked item needs an owner and next action before handover.

## 1. Discover and plan

- [ ] Identify the site owner, technical contact, users, critical services, and change window; document the intended scope and success criteria.
- [ ] Inventory the existing and planned router/firewall, switches, access points, uplinks, ISP handoff, and power; identify dependencies and unsupported equipment.
- [ ] Map the physical and logical topology, including uplinks, VLANs or other segments, gateways, and any management network.
- [ ] Agree an address plan, DHCP ranges and reservations, DNS and time sources, and how IP conflicts will be avoided.
- [ ] Define which user, guest, server, and management networks are needed and the allowed traffic between them and to the internet.
- [ ] Record current state, obtain an approved backup, and agree a rollback trigger, procedure, responsible person, and communication plan.

## 2. Install and configure

- [ ] Check rack or placement, ventilation, grounding, power capacity, and UPS coverage where required; label equipment and both ends of critical cables.
- [ ] Confirm link lights and negotiated speed/duplex on uplinks and representative client ports; investigate errors or unexpected negotiation.
- [ ] Apply approved device firmware and configuration; restrict management access to authorized administrators and management paths.
- [ ] Configure the agreed addressing, VLANs, port assignments, routing, DHCP, DNS, and time synchronization; verify uplinks carry only intended VLANs.
- [ ] Apply the approved firewall and inter-segment access rules, including guest isolation where applicable; disable unused ports or services according to site policy.
- [ ] If Wi-Fi is in scope, configure SSIDs, authentication, and segment mapping; verify coverage in the agreed locations.
- [ ] Configure monitoring, logging, time synchronization, and configuration backups to approved destinations; verify that alerts have an owner.

## 3. Validate from representative clients

Record source segment, destination, expected and actual result, time, and evidence for each test. Use approved targets and methods; do not run disruptive tests without a specific approved window.

- [ ] Confirm a wired client in each applicable segment receives the expected address, gateway, DNS settings, and lease or reservation.
- [ ] Confirm name resolution and access to the agreed internal services and external destinations from each applicable segment.
- [ ] Test the approved allowed **and denied** flows between segments and to the internet, including guest-to-internal and management access where applicable.
- [ ] If Wi-Fi is in scope, connect an authorized client to each SSID and repeat the applicable address, access, and isolation checks; verify coverage in agreed locations.
- [ ] Measure connectivity, latency, and throughput at agreed points against the site's preapproved thresholds; record the method and results.
- [ ] Confirm monitoring receives device status and logs, and send a test alert to its designated recipient.
- [ ] Verify a configuration backup exists and can be retrieved. If a restore or failover test is required, perform it only in an approved window and record the result.

## 4. Close and hand over

- [ ] Resolve or explicitly accept outstanding failures, blocked tests, and exceptions with an owner and due date.
- [ ] Update the private inventory, topology, address plan, support contacts, backup location, and recovery instructions to match the deployed state.
- [ ] Review results and residual risks with the site owner; record the acceptance decision, date, and follow-up actions.
