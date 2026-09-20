<img width="1893" height="831" alt="1000067385" src="https://github.com/user-attachments/assets/88fae493-979c-4f95-bd6d-639abd49acc5" />
# BDCustomBackPacks

Author: **BobyDev**
Version: **1.0.0**

BDCustomBackPacks is a PocketMine-MP 5 plugin that adds wearable and placeable custom backpacks with persistent storage, Customies-powered visuals, and a FormAPI management menu.

## Features

- Wearable backpacks that can be equipped in the chest slot.
- Placeable backpack entities that players can interact with in the world.
- Persistent backpack contents stored in item and entity NBT.
- Separate storage for each backpack item and placed backpack.
- Native virtual chest-style inventory windows for backpack storage.
- Automatic registration of the included Bedrock resource pack.
- Double-sneak shortcut for opening an equipped backpack.
- FormAPI menu for selecting and receiving backpack variants.
- Custom backpack names preserved when backpacks are placed and picked up.
- Safe pickup and placement flow for backpacks containing items.
- No crafting recipes are registered by the plugin.

## Backpack variants

The plugin includes 15 variants:

- Standard backpack
- Blue, green, purple, and red backpacks
- Axolotl backpack
- Blue, brown, cyan, and gold axolotl backpacks
- Big backpack
- Blue, green, purple, and red big backpacks

## Storage sizes

| Backpack type | Storage |
| --- | ---: |
| Standard backpacks | 27 slots |
| Axolotl backpacks | 27 slots |
| Big backpacks | 54 slots |

## Commands

| Command | Description | Permission |
| --- | --- | --- |
| `/backpack` | Opens the backpack selection menu. | `bdcustombackpacks.command` |
| `/backpack menu` | Opens the backpack selection menu. | `bdcustombackpacks.command` |
| `/backpack bags` | Opens the backpack selection menu. | `bdcustombackpacks.command` |
| `/backpack open` | Opens the currently equipped backpack. | `bdcustombackpacks.command` |
| `/backpack give <variant>` | Gives a selected backpack variant. | `bdcustombackpacks.admin` |

Example:

```text
/backpack give big_backpack_blue
```

The `bdcustombackpacks.command` permission is enabled by default. The `bdcustombackpacks.admin` permission is restricted to server operators by default.

## Dependencies

- [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) 5.x with API 5.0.0
- [Customies](https://github.com/CustomiesDevs/Customies)
- [FormAPI](https://github.com/jojoe77777/FormAPI)

## Main class

```text
BDCustomBackPacks\BobyDev\Main
```

## Altay target

The implementation targets Altay **5.44.5** and the Bedrock **1.26.50** protocol branch. Test the plugin with the matching server, Customies, FormAPI, and Bedrock client versions before production deployment.

## Installation

1. Download the `BDCustomBackPacks-1.0.0.phar` release asset.
2. Copy the PHAR into the PocketMine-MP `plugins/` directory.
3. Ensure Customies and FormAPI are installed and enabled.
4. Start or restart the server.
5. Allow the server resource pack to be sent to Bedrock clients when prompted.

The plugin automatically installs the included `BDCustomBackPacks_RP.mcpack` into the server resource-pack stack.

## Usage

Use `/backpack` to open the variant menu and receive a backpack. Equip a backpack in the chest slot, then use `/backpack open` or double-sneak to open its storage. Backpacks can also be placed in the world and interacted with directly. Sneak-interacting with a placed backpack picks it up while preserving its contents.

## Repository layout

```text
plugin.yml
resources/BDCustomBackPacks_RP.mcpack
src/BDCustomBackPacks/BobyDev/
├── Main.php
├── BackpackItem.php
├── BackpackEntity.php
├── BackpackEntities.php
└── BackpackVirtualInventory.php
```
